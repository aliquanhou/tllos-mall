<?php

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Adapters\RefundProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * P1-PI-05: Refund Recovery Service
 *
 * Scans for refunds stuck in UNKNOWN or PROCESSING beyond timeout thresholds
 * and actively queries the provider to resolve the final state.
 *
 * CRITICAL: Provider query is ALWAYS outside DB transaction.
 * Flow:
 *   scan (read) → lockForUpdate → commit scan state
 *   → Provider query (outside transaction)
 *   → persist result (separate transaction)
 *
 * Timeout thresholds:
 *   - UNKNOWN:    > 5 minutes  (network timeout, provider may have received)
 *   - PROCESSING: > 30 minutes (async provider, callback may be lost)
 *
 * This closes the "UNKNOWN black hole" where refunds are stuck forever
 * waiting for a callback that may never arrive.
 */
class RefundRecoveryService
{
    /**
     * Timeout thresholds in minutes.
     */
    private const UNKNOWN_TIMEOUT_MINUTES = 5;
    private const PROCESSING_TIMEOUT_MINUTES = 30;

    /**
     * Run one recovery cycle.
     * Returns count of refunds processed.
     */
    public function runRecovery(): int
    {
        $unknownThreshold = Carbon::now()->subMinutes(self::UNKNOWN_TIMEOUT_MINUTES);
        $processingThreshold = Carbon::now()->subMinutes(self::PROCESSING_TIMEOUT_MINUTES);

        // Find stuck refunds (read-only, no lock yet)
        $stuckRefunds = DB::table('order_refunds')
            ->where(function ($query) use ($unknownThreshold, $processingThreshold) {
                $query->where(function ($q) use ($unknownThreshold) {
                    $q->where('status', RefundStatus::UNKNOWN)
                        ->where('updated_at', '<', $unknownThreshold);
                })->orWhere(function ($q) use ($processingThreshold) {
                    $q->where('status', RefundStatus::PROCESSING)
                        ->where('updated_at', '<', $processingThreshold);
                });
            })
            ->orderBy('updated_at', 'asc')
            ->limit(50) // Process max 50 per cycle
            ->get();

        $processed = 0;

        foreach ($stuckRefunds as $refund) {
            try {
                $this->recoverSingleRefund($refund);
                $processed++;
            } catch (\Throwable $e) {
                Log::critical('退款恢复处理异常', [
                    'refund_id' => $refund->id,
                    'refund_no' => $refund->refund_no,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($processed > 0) {
            Log::info('退款恢复周期完成', [
                'processed' => $processed,
                'found' => $stuckRefunds->count(),
            ]);
        }

        return $processed;
    }

    /**
     * Recover a single stuck refund by querying the provider.
     *
     * CRITICAL: Provider query is OUTSIDE any DB transaction.
     */
    private function recoverSingleRefund(object $refund): void
    {
        // Step 1: Lock the refund record (short transaction)
        $locked = DB::transaction(function () use ($refund) {
            $current = DB::table('order_refunds')
                ->where('id', $refund->id)
                ->lockForUpdate()
                ->first();

            // Another worker may have already processed it
            if (!$current || !in_array((int)$current->status, [RefundStatus::UNKNOWN, RefundStatus::PROCESSING], true)) {
                return null;
            }

            // Mark as being recovered (touch updated_at to prevent duplicate processing)
            DB::table('order_refunds')
                ->where('id', $refund->id)
                ->update(['updated_at' => Carbon::now()]);

            return $current;
        });

        if (!$locked) {
            return; // Already resolved by another worker
        }

        // Step 2: Query provider (OUTSIDE DB transaction)
        try {
            $factory = app(RefundProviderFactory::class);
            $provider = $factory->make($locked->provider);

            if (!$provider || !$provider->isConfigured()) {
                Log::warning('退款恢复：Provider不可用，保持当前状态', [
                    'refund_no' => $locked->refund_no,
                    'provider' => $locked->provider,
                ]);
                return;
            }

            $result = $provider->query(
                $locked->refund_no,      // out_request_no / out_refund_no
                $locked->payment_no      // out_trade_no
            );
        } catch (\Throwable $e) {
            // Provider query failed → keep current state, will retry next cycle
            Log::warning('退款恢复：Provider查询异常，保持当前状态', [
                'refund_no' => $locked->refund_no,
                'provider' => $locked->provider,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // Step 3: Persist query result (separate transaction)
        DB::transaction(function () use ($locked, $result) {
            $current = DB::table('order_refunds')
                ->where('id', $locked->id)
                ->lockForUpdate()
                ->first();

            if (!$current || !in_array((int)$current->status, [RefundStatus::UNKNOWN, RefundStatus::PROCESSING], true)) {
                return; // Already resolved
            }

            $updateData = ['updated_at' => Carbon::now()];

            if ($result->isSuccess()) {
                // Provider confirmed refund success
                $updateData['status'] = RefundStatus::SUCCESS;
                $updateData['refunded_at'] = Carbon::now();
                $updateData['failure_reason'] = null;
                if ($result->providerRefundNo) {
                    $updateData['provider_refund_no'] = $result->providerRefundNo;
                }
                if ($result->providerTransactionNo) {
                    $updateData['provider_transaction_no'] = $result->providerTransactionNo;
                }
                Log::info('退款恢复：查询确认退款成功', [
                    'refund_no' => $locked->refund_no,
                    'provider' => $locked->provider,
                ]);
            } elseif ($result->isFailed()) {
                // Provider confirmed refund failed
                $updateData['status'] = RefundStatus::FAILED;
                $updateData['failure_reason'] = $result->message ?? 'Provider查询确认退款失败';
                Log::info('退款恢复：查询确认退款失败', [
                    'refund_no' => $locked->refund_no,
                    'provider' => $locked->provider,
                    'reason' => $result->message,
                ]);
            } elseif ($result->isProcessing()) {
                // Provider still processing → keep PROCESSING, will retry next cycle
                Log::info('退款恢复：Provider仍在处理中，保持PROCESSING', [
                    'refund_no' => $locked->refund_no,
                    'provider' => $locked->provider,
                ]);
                return;
            } else {
                // UNKNOWN → keep UNKNOWN, will retry next cycle
                Log::info('退款恢复：查询结果不确定，保持UNKNOWN', [
                    'refund_no' => $locked->refund_no,
                    'provider' => $locked->provider,
                    'reason' => $result->message,
                ]);
                return;
            }

            DB::table('order_refunds')
                ->where('id', $locked->id)
                ->update($updateData);
        });
    }

    /**
     * Recover a specific refund by ID (for manual trigger / testing).
     */
    public function recoverRefundById(int $refundId): array
    {
        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        if (!$refund) {
            return ['success' => false, 'message' => '退款单不存在'];
        }

        if (!in_array((int)$refund->status, [RefundStatus::UNKNOWN, RefundStatus::PROCESSING], true)) {
            return ['success' => false, 'message' => '当前状态不需要恢复: ' . $refund->status];
        }

        $this->recoverSingleRefund($refund);

        $updated = DB::table('order_refunds')->where('id', $refundId)->first();
        return ['success' => true, 'status' => $updated->status];
    }
}
