<?php

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * P1-REFUND-FOUNDATION: Unified Refund Domain Service
 *
 * Core principles:
 * 1. Third-party API calls are NEVER inside a DB transaction
 * 2. Refund amount check + record creation is an atomic critical section
 * 3. No fake SUCCESS — only callback/query can set SUCCESS
 * 4. Cumulative refund: paid - successful - processing >= requested
 *
 * This phase establishes the foundation. Provider integration (Alipay/WeChat
 * callback, query, reconciliation) belongs to P1-REFUND-PROVIDER-INTEGRATION.
 */
class RefundService
{
    /**
     * Calculate available refundable amount for an order.
     * Formula: paid_amount - successful_refund - processing_refund
     *
     * Processing refunds are included because they represent committed
     * third-party requests that may succeed. This prevents over-refund
     * when two refund requests are in flight simultaneously.
     */
    public function getAvailableRefundAmount(int $orderId): float
    {
        $order = Order::find($orderId);
        if (!$order) {
            return 0.0;
        }

        $lockedAmount = DB::table('order_refunds')
            ->where('order_id', $orderId)
            ->whereIn('status', RefundStatus::ACTIVE_REFUND_STATUSES)
            ->sum('refund_amount');

        return max(0.0, (float)$order->pay_amount - (float)$lockedAmount);
    }

    /**
     * Create a refund request (REQUESTED state).
     * Atomic: locks order row, checks cumulative amount, inserts record.
     *
     * @return array{success: bool, refund_id?: int, message?: string}
     */
    public function createRefund(array $data): array
    {
        $orderId = $data['order_id'];
        $userId = $data['user_id'];
        $amount = (float)$data['refund_amount'];
        $reason = $data['reason'] ?? '';

        return DB::transaction(function () use ($orderId, $userId, $amount, $reason, $data) {
            // Lock order row to prevent concurrent refund over-commit
            $order = Order::where('id', $orderId)->lockForUpdate()->first();
            if (!$order) {
                return ['success' => false, 'message' => '订单不存在'];
            }

            if (!in_array($order->status, [1, 2, 3])) {
                return ['success' => false, 'message' => '当前订单状态不支持退款'];
            }

            // Cumulative refund check inside the locked transaction
            $lockedAmount = DB::table('order_refunds')
                ->where('order_id', $orderId)
                ->whereIn('status', RefundStatus::ACTIVE_REFUND_STATUSES)
                ->sum('refund_amount');

            $available = (float)$order->pay_amount - (float)$lockedAmount;
            if ($amount > $available + 0.001) {
                Log::warning('退款金额超过可退金额', [
                    'order_id' => $orderId,
                    'requested' => $amount,
                    'available' => $available,
                    'locked' => $lockedAmount,
                ]);
                return ['success' => false, 'message' => '退款金额超过可退金额，可退金额：¥' . number_format($available, 2)];
            }

            // Determine payment identity
            $payment = DB::table('payments')
                ->where('order_no', $order->order_no)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->first();

            $refundNo = 'RF' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $refundId = DB::table('order_refunds')->insertGetId([
                'refund_no' => $refundNo,
                'order_id' => $order->id,
                'order_item_id' => $data['order_item_id'] ?? 0,
                'user_id' => $userId,
                'merchant_id' => $order->merchant_id,
                'type' => $data['type'] ?? 1,
                'refund_amount' => $amount,
                'reason' => $reason,
                'description' => $data['description'] ?? '',
                'images' => !empty($data['images']) ? json_encode($data['images']) : null,
                'status' => RefundStatus::REQUESTED,
                'payment_id' => $payment?->id,
                'payment_no' => $payment?->payment_no,
                'provider' => $this->mapPayTypeToProvider($order->pay_type),
                'provider_transaction_no' => $payment?->third_payment_no,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return [
                'success' => true,
                'refund_id' => $refundId,
                'refund_no' => $refundNo,
                'status' => RefundStatus::REQUESTED,
            ];
        });
    }

    /**
     * Admin approves a refund request.
     *
     * P1-REFUND-FOUNDATION: Approval moves to PROCESSING, NOT SUCCESS.
     * No fake third-party refund number. No direct order status change.
     * Provider integration (actual refund API call) is the next phase.
     *
     * @return array{success: bool, message?: string}
     */
    public function approveRefund(int $refundId, int $adminId = 0): array
    {
        return DB::transaction(function () use ($refundId, $adminId) {
            $refund = DB::table('order_refunds')
                ->where('id', $refundId)
                ->lockForUpdate()
                ->first();

            if (!$refund) {
                return ['success' => false, 'message' => '退款单不存在'];
            }

            if ($refund->status != RefundStatus::REQUESTED) {
                return ['success' => false, 'message' => '当前状态不能审核，状态：' . RefundStatus::label($refund->status)];
            }

            // Move to PROCESSING — third-party refund will be triggered in
            // P1-REFUND-PROVIDER-INTEGRATION. For now, record the approval
            // and mark as processing. NO fake SUCCESS, NO fake provider_refund_no.
            DB::table('order_refunds')->where('id', $refundId)->update([
                'status' => RefundStatus::PROCESSING,
                'updated_at' => Carbon::now(),
            ]);

            Log::info('退款审核通过，进入处理中状态', [
                'refund_id' => $refundId,
                'refund_no' => $refund->refund_no,
                'order_id' => $refund->order_id,
                'amount' => $refund->refund_amount,
                'admin_id' => $adminId,
            ]);

            return ['success' => true, 'status' => RefundStatus::PROCESSING];
        });
    }

    /**
     * Admin rejects a refund request.
     */
    public function rejectRefund(int $refundId, string $reason = '', int $adminId = 0): array
    {
        return DB::transaction(function () use ($refundId, $reason, $adminId) {
            $refund = DB::table('order_refunds')
                ->where('id', $refundId)
                ->lockForUpdate()
                ->first();

            if (!$refund) {
                return ['success' => false, 'message' => '退款单不存在'];
            }

            if ($refund->status != RefundStatus::REQUESTED) {
                return ['success' => false, 'message' => '当前状态不能拒绝'];
            }

            DB::table('order_refunds')->where('id', $refundId)->update([
                'status' => RefundStatus::REJECTED,
                'refuse_reason' => $reason ?: '不符合退款条件',
                'updated_at' => Carbon::now(),
            ]);

            return ['success' => true];
        });
    }

    /**
     * User cancels a refund request (only if REQUESTED).
     */
    public function cancelRefund(int $refundId, int $userId): array
    {
        $refund = DB::table('order_refunds')
            ->where('id', $refundId)
            ->where('user_id', $userId)
            ->first();

        if (!$refund) {
            return ['success' => false, 'message' => '退款单不存在'];
        }

        if ($refund->status != RefundStatus::REQUESTED) {
            return ['success' => false, 'message' => '当前状态不能取消'];
        }

        DB::table('order_refunds')->where('id', $refundId)->update([
            'status' => RefundStatus::CANCELLED,
            'updated_at' => Carbon::now(),
        ]);

        return ['success' => true];
    }

    /**
     * Map order pay_type to provider string.
     */
    private function mapPayTypeToProvider(int $payType): ?string
    {
        return match ($payType) {
            1 => 'wechat',
            2 => 'alipay',
            3 => 'balance',
            4 => 'points',
            default => null,
        };
    }
}
