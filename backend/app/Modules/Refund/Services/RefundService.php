<?php

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Contracts\RefundProviderResult;
use App\Modules\Refund\Adapters\RefundProviderFactory;
use App\Modules\Order\Models\Order;
use App\Modules\Inventory\Services\InventoryLedgerService;
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
     * P1-PI-02: Initiate provider refund for an existing refund record.
     *
     * CRITICAL ARCHITECTURE:
     *   1. Local state (PROCESSING) is ALREADY committed before this method is called
     *   2. Provider API call happens OUTSIDE any DB transaction
     *   3. Result persistence happens in a SEPARATE DB transaction
     *   4. If persistence fails, refund stays PROCESSING (recoverable), NOT FAILED
     *
     * This prevents the dangerous scenario:
     *   Provider refund succeeds → our DB write fails → we think it failed → retry → double refund
     *
     * State transitions:
     *   PROCESSING + provider success    → SUCCESS (Alipay sync)
     *   PROCESSING + provider processing → PROCESSING (WeChat async, save provider_refund_no)
     *   PROCESSING + provider failed     → FAILED
     *   PROCESSING + provider unknown    → UNKNOWN (timeout, needs query)
     *   UNKNOWN    + retry               → re-uses same refund_no (idempotent)
     *
     * @param int $refundId
     * @return array{success: bool, status: int, message?: string, result?: RefundProviderResult}
     */
    public function initiateProviderRefund(int $refundId): array
    {
        // Step 1: Read refund record (read-only, no transaction needed)
        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        if (!$refund) {
            return ['success' => false, 'status' => 0, 'message' => '退款单不存在'];
        }

        // Only PROCESSING or UNKNOWN can initiate/retry provider refund
        if (!in_array($refund->status, [RefundStatus::PROCESSING, RefundStatus::UNKNOWN], true)) {
            return [
                'success' => false,
                'status' => $refund->status,
                'message' => '当前状态不能发起退款，status=' . $refund->status,
            ];
        }

        // Step 2: Verify payment record exists for identity binding
        $payment = DB::table('payments')->where('payment_no', $refund->payment_no)->first();
        if (!$payment) {
            return ['success' => false, 'status' => $refund->status, 'message' => '支付记录不存在，无法建立Provider身份'];
        }

        // Step 3: Resolve provider adapter
        $factory = app(RefundProviderFactory::class);
        $provider = $factory->make($refund->provider);
        if (!$provider) {
            return ['success' => false, 'status' => $refund->status, 'message' => '不支持的支付渠道: ' . $refund->provider];
        }

        // Production + unconfigured = FAIL CLOSED (never mock)
        if (!$provider->isConfigured()) {
            return ['success' => false, 'status' => $refund->status, 'message' => '支付渠道未配置完成: ' . $refund->provider];
        }

        // Step 4: CRITICAL — Provider API call is OUTSIDE any DB transaction
        // The local PROCESSING state was already committed by approveRefund().
        // We must NOT wrap this HTTP call in DB::transaction().
        try {
            $result = $provider->refund([
                'out_trade_no' => $refund->payment_no,
                'out_request_no' => $refund->refund_no,  // Idempotency key = TLL refund_no
                'amount' => (string)$refund->refund_amount,
                'payment_amount' => (string)$payment->amount,  // Original payment amount for provider
                'provider_transaction_no' => $payment->third_payment_no ?? '',  // Provider transaction ID
                'reason' => $refund->reason ?? '退款',
                'notify_url' => config('app.url') . '/api/v1/payment/refund-notify/' . $refund->provider,
            ]);
        } catch (\Throwable $e) {
            // Double protection: adapter should catch this, but if it leaks,
            // treat as UNKNOWN (provider may have received the request)
            Log::warning('Provider退款调用异常泄漏，进入UNKNOWN', [
                'refund_id' => $refundId,
                'refund_no' => $refund->refund_no,
                'provider' => $refund->provider,
                'error' => $e->getMessage(),
            ]);
            $result = RefundProviderResult::unknown('Provider调用异常: ' . $e->getMessage());
        }

        // Step 5: Persist result in a SEPARATE DB transaction.
        // If this fails, refund stays PROCESSING (recoverable). We do NOT claim FAILED
        // because the provider may have already processed the refund successfully.
        try {
            $result = DB::transaction(function () use ($refundId, $result, $refund) {
                $locked = DB::table('order_refunds')
                    ->where('id', $refundId)
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    throw new \RuntimeException('退款记录在事务中消失');
                }

                if ($result->isSuccess()) {
                    // Provider confirmed refund completed (Alipay synchronous)
                    DB::table('order_refunds')->where('id', $refundId)->update([
                        'status' => RefundStatus::SUCCESS,
                        'provider_refund_no' => $result->providerRefundNo !== '' ? $result->providerRefundNo : $locked->provider_refund_no,
                        'provider_transaction_no' => $result->providerTransactionNo !== '' ? $result->providerTransactionNo : $locked->provider_transaction_no,
                        'refunded_at' => Carbon::now(),
                        'failure_reason' => null,
                        'updated_at' => Carbon::now(),
                    ]);
                    Log::info('退款成功', [
                        'refund_id' => $refundId,
                        'refund_no' => $refund->refund_no,
                        'provider' => $refund->provider,
                        'amount' => $refund->refund_amount,
                    ]);

                    // P2-02: Record inventory REFUND event (restore stock)
                    try {
                        $orderItem = DB::table('order_items')->where('id', $locked->order_item_id)->first();
                        if ($orderItem && $orderItem->sku_id) {
                            $inventoryService = app(InventoryLedgerService::class);
                            $quantity = intval($orderItem->quantity);
                            $inventoryService->refund(
                                $orderItem->sku_id,
                                $orderItem->product_id,
                                $quantity,
                                $refundId,
                                $locked->refund_no
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error('Inventory refund event failed', [
                            'refund_no' => $locked->refund_no,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    return ['success' => true, 'status' => RefundStatus::SUCCESS, 'result' => $result];
                }

                if ($result->isProcessing()) {
                    // Provider accepted async request (WeChat). Keep PROCESSING,
                    // save provider identity for callback matching.
                    DB::table('order_refunds')->where('id', $refundId)->update([
                        'provider_refund_no' => $result->providerRefundNo !== '' ? $result->providerRefundNo : $locked->provider_refund_no,
                        'provider_transaction_no' => $result->providerTransactionNo !== '' ? $result->providerTransactionNo : $locked->provider_transaction_no,
                        'updated_at' => Carbon::now(),
                    ]);
                    return ['success' => true, 'status' => RefundStatus::PROCESSING, 'result' => $result];
                }

                if ($result->isFailed()) {
                    // Provider explicitly rejected the refund
                    DB::table('order_refunds')->where('id', $refundId)->update([
                        'status' => RefundStatus::FAILED,
                        'failure_reason' => $result->message,
                        'updated_at' => Carbon::now(),
                    ]);
                    Log::warning('退款失败', [
                        'refund_id' => $refundId,
                        'refund_no' => $refund->refund_no,
                        'provider' => $refund->provider,
                        'reason' => $result->message,
                    ]);
                    return ['success' => false, 'status' => RefundStatus::FAILED, 'result' => $result];
                }

                // UNKNOWN: timeout / uncertain delivery. Must query to resolve.
                DB::table('order_refunds')->where('id', $refundId)->update([
                    'status' => RefundStatus::UNKNOWN,
                    'failure_reason' => $result->message,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => Carbon::now(),
                ]);
                Log::warning('退款进入未知状态，需查询确认', [
                    'refund_id' => $refundId,
                    'refund_no' => $refund->refund_no,
                    'provider' => $refund->provider,
                    'reason' => $result->message,
                ]);
                return ['success' => false, 'status' => RefundStatus::UNKNOWN, 'result' => $result];
            });

            // P2-UI-04: Send refund success notification
            if ($result['status'] == RefundStatus::SUCCESS) {
                try {
                    $order = DB::table('orders')->where('id', $refund->order_id)->first();
                    if ($order) {
                        $notificationService = app(\App\Modules\UserCenter\Services\NotificationService::class);
                        $notificationService->sendRefundSuccess(
                            $order->user_id,
                            $refund->refund_no,
                            $refund->refund_amount
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Refund notification failed', [
                        'refund_no' => $refund->refund_no,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $result;
        } catch (\Throwable $e) {
            // ============================================================
            // CRITICAL (Acceptance #7):
            // Provider may have already processed the refund, but our DB
            // write failed. Do NOT claim FAILED. Keep PROCESSING as a
            // recoverable state. The refund_no remains the same for retry.
            // ============================================================
            Log::critical('退款结果持久化失败，退款保持处理中状态（可恢复，需人工/查询确认）', [
                'refund_id' => $refundId,
                'refund_no' => $refund->refund_no,
                'provider' => $refund->provider,
                'provider_result_status' => $result->status,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'status' => RefundStatus::PROCESSING,
                'message' => '退款结果保存失败，保持处理中状态，需查询确认',
            ];
        }
    }

    /**
     * P1-R2: Retry idempotency — get existing refund by refund_no.
     *
     * Used by Provider Integration retry logic: if a refund already exists
     * for a given refund_no, return it instead of creating a duplicate.
     */
    public function getRefundByNo(string $refundNo): ?object
    {
        return DB::table('order_refunds')->where('refund_no', $refundNo)->first();
    }

    /**
     * P1-R2: Retry idempotency — initiate or retry provider refund for an existing refund.
     *
     * CRITICAL: This method does NOT create a new refund record. It operates on
     * an existing refund_id. The same refund_no is reused across all retries,
     * ensuring provider-side idempotency (out_request_no = refund_no).
     *
     * Flow:
     *   REQUESTED → approve → PROCESSING → provider call → timeout → UNKNOWN
     *              → retryRefund() → re-uses SAME refund_no → provider call
     *
     * This prevents the dangerous scenario:
     *   RF001 → provider accepted → timeout → UNKNOWN → retry creates RF002 → duplicate refund
     *
     * Returns the existing refund record (never creates a new one).
     * Provider Integration phase will implement the actual API call inside this method.
     */
    public function retryRefund(int $refundId): array
    {
        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        if (!$refund) {
            return ['success' => false, 'message' => '退款单不存在'];
        }

        // Only PROCESSING or UNKNOWN refunds can be retried
        if (!in_array($refund->status, [RefundStatus::PROCESSING, RefundStatus::UNKNOWN])) {
            return [
                'success' => false,
                'message' => '当前状态不支持重试，status=' . $refund->status,
                'refund' => $refund,
            ];
        }

        // Idempotency: return existing refund, increment attempts
        DB::table('order_refunds')->where('id', $refundId)->update([
            'attempts' => DB::raw('attempts + 1'),
            'updated_at' => Carbon::now(),
        ]);

        $refund = DB::table('order_refunds')->where('id', $refundId)->first();

        Log::info('退款重试（幂等，复用同一refund_no）', [
            'refund_id' => $refundId,
            'refund_no' => $refund->refund_no,
            'attempts' => $refund->attempts,
            'status' => $refund->status,
        ]);

        // Provider Integration phase will call actual Alipay/WeChat API here
        // using $refund->refund_no as out_request_no (idempotency key)
        return [
            'success' => true,
            'refund' => $refund,
            'refund_no' => $refund->refund_no,
            'attempts' => $refund->attempts,
            'message' => '重试已登记，复用同一退款身份 ' . $refund->refund_no,
        ];
    }

    /**
     * P1-PRECONDITION: Mark refund as UNKNOWN due to provider timeout/uncertainty.
     *
     * CRITICAL: HTTP timeout / connection reset does NOT mean refund failed.
     * The provider may have already processed the refund. Must enter UNKNOWN
     * and resolve via Query, NOT directly FAILED.
     *
     * Only PROCESSING can move to UNKNOWN.
     * UNKNOWN continues to lock refund amount until resolved.
     */
    public function markAsUnknown(int $refundId, string $reason = ''): array
    {
        return DB::transaction(function () use ($refundId, $reason) {
            $refund = DB::table('order_refunds')
                ->where('id', $refundId)
                ->lockForUpdate()
                ->first();

            if (!$refund) {
                return ['success' => false, 'message' => '退款单不存在'];
            }

            if ($refund->status != RefundStatus::PROCESSING) {
                return ['success' => false, 'message' => '只有处理中状态可以标记为未知'];
            }

            DB::table('order_refunds')->where('id', $refundId)->update([
                'status' => RefundStatus::UNKNOWN,
                'failure_reason' => $reason ?: '第三方调用超时或响应不确定，进入未知状态等待查询确认',
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => Carbon::now(),
            ]);

            Log::warning('退款进入未知状态（需查询确认）', [
                'refund_id' => $refundId,
                'refund_no' => $refund->refund_no,
                'reason' => $reason,
            ]);

            return ['success' => true, 'status' => RefundStatus::UNKNOWN];
        });
    }

    /**
     * P1-PRECONDITION: Resolve UNKNOWN refund via provider query result.
     *
     * UNKNOWN → SUCCESS: provider confirmed refund completed
     *   - amount remains locked (it was locked in UNKNOWN, correctly)
     *   - set provider_refund_no if provided
     *
     * UNKNOWN → FAILED: provider confirmed refund did not happen
     *   - amount is released (FAILED not in ACTIVE_REFUND_STATUSES)
     *
     * Only UNKNOWN can be resolved via this method.
     */
    public function resolveUnknown(int $refundId, bool $success, string $providerRefundNo = '', string $failureReason = ''): array
    {
        return DB::transaction(function () use ($refundId, $success, $providerRefundNo, $failureReason) {
            $refund = DB::table('order_refunds')
                ->where('id', $refundId)
                ->lockForUpdate()
                ->first();

            if (!$refund) {
                return ['success' => false, 'message' => '退款单不存在'];
            }

            if ($refund->status != RefundStatus::UNKNOWN) {
                return ['success' => false, 'message' => '只有未知状态可以通过查询解析'];
            }

            if ($success) {
                DB::table('order_refunds')->where('id', $refundId)->update([
                    'status' => RefundStatus::SUCCESS,
                    'provider_refund_no' => $providerRefundNo ?: $refund->provider_refund_no,
                    'refunded_at' => Carbon::now(),
                    'failure_reason' => null,
                    'updated_at' => Carbon::now(),
                ]);
                Log::info('未知退款查询确认为成功', [
                    'refund_id' => $refundId,
                    'refund_no' => $refund->refund_no,
                    'provider_refund_no' => $providerRefundNo,
                ]);
                return ['success' => true, 'status' => RefundStatus::SUCCESS];
            } else {
                DB::table('order_refunds')->where('id', $refundId)->update([
                    'status' => RefundStatus::FAILED,
                    'failure_reason' => $failureReason ?: '第三方查询确认退款未发生',
                    'updated_at' => Carbon::now(),
                ]);
                Log::info('未知退款查询确认为失败，金额已释放', [
                    'refund_id' => $refundId,
                    'refund_no' => $refund->refund_no,
                    'reason' => $failureReason,
                ]);
                return ['success' => true, 'status' => RefundStatus::FAILED];
            }
        });
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
