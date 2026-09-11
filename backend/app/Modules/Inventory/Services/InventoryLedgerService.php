<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryLedgerService
{
    /**
     * Record an inventory event in the ledger.
     * Idempotent by idempotency_key.
     */
    public function recordEvent(array $params): bool
    {
        try {
            // Get current stock for this SKU
            $currentStock = $this->getAvailableStock($params['sku_id']);

            DB::table('inventory_ledger')->insert([
                'sku_id' => $params['sku_id'],
                'product_id' => $params['product_id'] ?? 0,
                'event_type' => $params['event_type'],
                'quantity_delta' => $params['quantity_delta'],
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $params['quantity_delta'],
                'reference_type' => $params['reference_type'],
                'reference_id' => $params['reference_id'] ?? 0,
                'idempotency_key' => $params['idempotency_key'],
                'operator_id' => $params['operator_id'] ?? 0,
                'source_version' => $params['source_version'] ?? 0,
                'metadata' => isset($params['metadata']) ? json_encode($params['metadata']) : null,
                'created_at' => now(),
            ]);

            // Also update the cached stock on product_skus (dual-write for compatibility)
            if (!empty($params['sku_id'])) {
                DB::table('product_skus')
                    ->where('id', $params['sku_id'])
                    ->update(['stock' => DB::raw('stock + ' . $params['quantity_delta'])]);
            }

            return true;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Idempotent: duplicate key = already processed, not an error
            Log::info('Inventory event skipped (duplicate idempotency_key)', [
                'key' => $params['idempotency_key'] ?? 'unknown',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Inventory event failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get available stock for a SKU from ledger.
     */
    public function getAvailableStock(int $skuId): int
    {
        $result = DB::table('inventory_ledger')
            ->where('sku_id', $skuId)
            ->where('event_type', '!=', 'RESERVE') // RESERVE is temporary, not reducing available permanently
            ->sum('quantity_delta');

        return intval($result);
    }

    /**
     * Get reserved stock for a SKU.
     */
    public function getReservedStock(int $skuId): int
    {
        $reserved = DB::table('inventory_ledger')
            ->where('sku_id', $skuId)
            ->where('event_type', 'RESERVE')
            ->sum('quantity_delta');

        $released = DB::table('inventory_ledger')
            ->where('sku_id', $skuId)
            ->where('event_type', 'RELEASE')
            ->sum('quantity_delta');

        return abs(intval($reserved)) - intval($released);
    }

    /**
     * Get sold stock for a SKU.
     */
    public function getSoldStock(int $skuId): int
    {
        $sold = DB::table('inventory_ledger')
            ->where('sku_id', $skuId)
            ->where('event_type', 'SALE')
            ->sum('quantity_delta');

        $refunded = DB::table('inventory_ledger')
            ->where('sku_id', $skuId)
            ->where('event_type', 'REFUND')
            ->sum('quantity_delta');

        return abs(intval($sold)) - intval($refunded);
    }

    /**
     * Reserve stock when order is created.
     */
    public function reserve(int $skuId, int $productId, int $quantity, int $orderId, string $orderNo = ''): bool
    {
        return $this->recordEvent([
            'sku_id' => $skuId,
            'product_id' => $productId,
            'event_type' => 'RESERVE',
            'quantity_delta' => -$quantity,
            'reference_type' => 'ORDER',
            'reference_id' => $orderId,
            'idempotency_key' => "RESERVE_{$orderNo}_{$skuId}",
            'metadata' => ['reason' => 'order_created', 'order_no' => $orderNo],
        ]);
    }

    /**
     * Confirm sale when payment succeeds.
     */
    public function sale(int $skuId, int $productId, int $quantity, int $orderId, string $orderNo = ''): bool
    {
        return $this->recordEvent([
            'sku_id' => $skuId,
            'product_id' => $productId,
            'event_type' => 'SALE',
            'quantity_delta' => -$quantity,
            'reference_type' => 'ORDER',
            'reference_id' => $orderId,
            'idempotency_key' => "SALE_{$orderNo}_{$skuId}",
            'metadata' => ['reason' => 'payment_success', 'order_no' => $orderNo],
        ]);
    }

    /**
     * Release reserved stock when order is cancelled.
     */
    public function release(int $skuId, int $productId, int $quantity, int $orderId, string $orderNo = ''): bool
    {
        return $this->recordEvent([
            'sku_id' => $skuId,
            'product_id' => $productId,
            'event_type' => 'RELEASE',
            'quantity_delta' => $quantity,
            'reference_type' => 'ORDER',
            'reference_id' => $orderId,
            'idempotency_key' => "RELEASE_{$orderNo}_{$skuId}",
            'metadata' => ['reason' => 'order_cancelled', 'order_no' => $orderNo],
        ]);
    }

    /**
     * Restore stock when refund succeeds.
     */
    public function refund(int $skuId, int $productId, int $quantity, int $refundId, string $refundNo = ''): bool
    {
        return $this->recordEvent([
            'sku_id' => $skuId,
            'product_id' => $productId,
            'event_type' => 'REFUND',
            'quantity_delta' => $quantity,
            'reference_type' => 'REFUND',
            'reference_id' => $refundId,
            'idempotency_key' => "REFUND_{$refundNo}_{$skuId}",
            'metadata' => ['reason' => 'refund_success', 'refund_no' => $refundNo],
        ]);
    }
}
