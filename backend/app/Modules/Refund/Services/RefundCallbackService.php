<?php

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Payment\Services\AlipayService;
use App\Modules\Payment\Services\WechatPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * P1-PI-03: Refund Callback Security & State Machine
 *
 * Unified refund callback handler. Controller must NOT directly modify refund state.
 *
 * Security flow:
 *   Provider Callback
 *     → Signature Verify (Alipay RSA / WeChat RSA+AES-GCM)
 *     → Identity Match (out_request_no/out_refund_no → refund_no, NEVER order_no)
 *     → Amount Verify (callback amount == local refund_amount)
 *     → State Machine Constraint (only PROCESSING/UNKNOWN → SUCCESS/FAILED)
 *     → Idempotent Update (lockForUpdate, duplicate = NO-OP)
 *
 * Critical rules:
 * 1. Forged refund notification (unknown refund_no) must be REJECTED
 * 2. Amount mismatch must be REJECTED
 * 3. SUCCESS → anything is FORBIDDEN
 * 4. FAILED → SUCCESS is FORBIDDEN
 * 5. Duplicate callback must produce NO side effects
 * 6. DB failure must rollback, not silently succeed
 */
class RefundCallbackService
{
    /**
     * Handle Alipay refund callback.
     * Alipay refund notify fields: out_request_no, trade_no, refund_amount, refund_status
     */
    public function handleAlipayCallback(array $data): array
    {
        // Gate 2: Signature validation
        $alipay = app(AlipayService::class);
        $verifyResult = $alipay->verifyNotify($data);

        if (!($verifyResult['success'] ?? false)) {
            Log::warning('支付宝退款回调签名验证失败', [
                'out_request_no' => $data['out_request_no'] ?? '',
                'reason' => $verifyResult['message'] ?? 'unknown',
            ]);
            return ['success' => false, 'message' => '签名验证失败', 'http_code' => 400];
        }

        // Gate 3: Identity — must use out_request_no (= refund_no), NEVER order_no
        $refundNo = $data['out_request_no'] ?? '';
        if (empty($refundNo)) {
            Log::warning('支付宝退款回调缺少out_request_no', $data);
            return ['success' => false, 'message' => '缺少退款单号', 'http_code' => 400];
        }

        // Gate 4: Amount — callback refund_amount must match local
        $callbackAmount = (float)($data['refund_amount'] ?? 0);
        $callbackStatus = $data['refund_status'] ?? 'REFUND_SUCCESS';

        return $this->processCallback('alipay', $refundNo, $callbackAmount, $callbackStatus, $data);
    }

    /**
     * Handle WeChat refund callback.
     * WeChat refund notify (after decrypt): out_refund_no, refund_id, refund_status, amount.refund (cents)
     */
    public function handleWechatCallback(array $data): array
    {
        // Gate 2: Signature validation (RSA verify + AES-GCM decrypt inside verifyNotify)
        $wechat = app(WechatPayService::class);
        $verifyResult = $wechat->verifyNotify($data);

        if (!($verifyResult['success'] ?? false)) {
            Log::warning('微信退款回调签名验证失败', [
                'out_refund_no' => $data['out_refund_no'] ?? '',
                'reason' => $verifyResult['message'] ?? 'unknown',
            ]);
            return ['success' => false, 'message' => '签名验证失败', 'http_code' => 400];
        }

        // Gate 3: Identity — must use out_refund_no (= refund_no), NEVER order_no
        $refundNo = $data['out_refund_no'] ?? '';
        if (empty($refundNo)) {
            Log::warning('微信退款回调缺少out_refund_no', $data);
            return ['success' => false, 'message' => '缺少退款单号', 'http_code' => 400];
        }

        // Gate 4: Amount — WeChat uses cents, convert to yuan
        $callbackAmount = (float)(($data['amount']['refund'] ?? 0) / 100);
        $callbackStatus = $data['refund_status'] ?? 'SUCCESS';

        return $this->processCallback('wechat', $refundNo, $callbackAmount, $callbackStatus, $data);
    }

    /**
     * Core callback processing — shared by all providers.
     * CRITICAL: Everything inside a DB transaction with lockForUpdate for idempotency.
     */
    private function processCallback(
        string $provider,
        string $refundNo,
        float $callbackAmount,
        string $callbackStatus,
        array $rawData
    ): array {
        try {
            return DB::transaction(function () use ($provider, $refundNo, $callbackAmount, $callbackStatus, $rawData) {
                // Gate 3: Exact identity match by refund_no (lockForUpdate for concurrency/idempotency)
                $refund = DB::table('order_refunds')
                    ->where('refund_no', $refundNo)
                    ->lockForUpdate()
                    ->first();

                if (!$refund) {
                    // Forged callback: refund_no does not exist. NEVER create refund from callback.
                    Log::warning('退款回调：refund_no不存在，拒绝（可能是伪造通知）', [
                        'provider' => $provider,
                        'refund_no' => $refundNo,
                    ]);
                    return ['success' => false, 'message' => '退款单不存在', 'http_code' => 404];
                }

                // Gate 4: Amount verification — exact match using bccomp
                if (bccomp((string)$callbackAmount, (string)$refund->refund_amount, 2) !== 0) {
                    Log::critical('退款回调金额不一致，拒绝状态更新', [
                        'provider' => $provider,
                        'refund_no' => $refundNo,
                        'local_amount' => $refund->refund_amount,
                        'callback_amount' => $callbackAmount,
                    ]);
                    return ['success' => false, 'message' => '退款金额不一致', 'http_code' => 400];
                }

                // Gate 5: Map provider status to canonical status
                $targetStatus = $this->mapCallbackStatus($provider, $callbackStatus);

                // Gate 6: Idempotency — already in target state = NO-OP
                if ((int)$refund->status === $targetStatus) {
                    return [
                        'success' => true,
                        'message' => '重复通知，状态未变化',
                        'idempotent' => true,
                        'status' => $targetStatus,
                    ];
                }

                // Gate 5: State machine constraint — only PROCESSING/UNKNOWN can transition
                if (!in_array((int)$refund->status, [RefundStatus::PROCESSING, RefundStatus::UNKNOWN], true)) {
                    Log::warning('退款回调：当前状态不允许转换', [
                        'provider' => $provider,
                        'refund_no' => $refundNo,
                        'current_status' => $refund->status,
                        'target_status' => $targetStatus,
                    ]);
                    return ['success' => false, 'message' => '当前状态不允许转换', 'http_code' => 409];
                }

                // Execute state transition
                $updateData = [
                    'status' => $targetStatus,
                    'updated_at' => Carbon::now(),
                ];

                if ($targetStatus === RefundStatus::SUCCESS) {
                    $updateData['refunded_at'] = Carbon::now();
                    $updateData['failure_reason'] = null;
                    // Preserve provider identity if present in callback
                    if (!empty($rawData['trade_no'])) {
                        $updateData['provider_transaction_no'] = $rawData['trade_no'];
                    }
                    if (!empty($rawData['refund_id'])) {
                        $updateData['provider_refund_no'] = $rawData['refund_id'];
                    }
                } elseif ($targetStatus === RefundStatus::FAILED) {
                    $updateData['failure_reason'] = $rawData['fail_reason'] ?? $rawData['reason'] ?? 'Provider退款失败';
                }

                DB::table('order_refunds')->where('id', $refund->id)->update($updateData);

                Log::info('退款回调状态更新成功', [
                    'provider' => $provider,
                    'refund_no' => $refundNo,
                    'from_status' => $refund->status,
                    'to_status' => $targetStatus,
                ]);

                return ['success' => true, 'status' => $targetStatus];
            });
        } catch (\Throwable $e) {
            // Gate 7: DB failure — rollback, do not silently succeed
            Log::critical('退款回调处理失败，事务已回滚', [
                'provider' => $provider,
                'refund_no' => $refundNo,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => '回调处理失败: ' . $e->getMessage(), 'http_code' => 500];
        }
    }

    /**
     * Map provider-specific refund status to canonical RefundStatus.
     */
    private function mapCallbackStatus(string $provider, string $status): int
    {
        $status = strtoupper($status);

        $successStates = ['REFUND_SUCCESS', 'SUCCESS'];
        $failedStates = ['REFUND_FAIL', 'REFUND_CLOSED', 'FAIL', 'CLOSED', 'CHANGE'];

        if (in_array($status, $successStates, true)) {
            return RefundStatus::SUCCESS;
        }

        if (in_array($status, $failedStates, true)) {
            return RefundStatus::FAILED;
        }

        // PROCESSING means provider still processing — keep current state
        return RefundStatus::PROCESSING;
    }
}
