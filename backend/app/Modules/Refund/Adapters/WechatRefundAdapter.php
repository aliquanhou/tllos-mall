<?php

namespace App\Modules\Refund\Adapters;

use App\Modules\Refund\Contracts\RefundProviderInterface;
use App\Modules\Refund\Contracts\RefundProviderResult;
use App\Modules\Payment\Services\WechatPayService;
use Illuminate\Support\Facades\Log;

/**
 * P1-PI-01: WeChat Refund Adapter
 *
 * Wraps existing WechatPayService::refund() and normalizes the result
 * into RefundProviderResult.
 *
 * WeChat refund API (POST /v3/refund/domestic/refunds) is ASYNCHRONOUS:
 *   - Request: out_trade_no, out_refund_no, amount, notify_url
 *   - HTTP 200 → request ACCEPTED (not necessarily completed)
 *   - Response: refund_id (WeChat refund ID), out_refund_no
 *   - Final result delivered via notify_url callback
 *
 * IMPORTANT: WeChat refund HAS an independent refund ID (refund_id).
 * provider_refund_no = WeChat refund_id is the canonical provider identity.
 *
 * Field mapping:
 *   TLL payment_no          → WeChat out_trade_no
 *   TLL refund_no           → WeChat out_refund_no (idempotency key)
 *   WeChat transaction_id   → TLL payments.third_payment_no
 *   WeChat refund_id        → TLL order_refunds.provider_refund_no
 */
class WechatRefundAdapter implements RefundProviderInterface
{
    public function __construct(
        private readonly WechatPayService $wechatService,
    ) {}

    public function refund(array $params): RefundProviderResult
    {
        // Production + incomplete config → FAIL CLOSED
        if (!$this->isConfigured()) {
            return RefundProviderResult::failed(
                '微信支付未配置完成，生产环境禁止使用沙箱或模拟退款',
            );
        }

        try {
            $result = $this->wechatService->refund([
                'out_trade_no' => $params['out_trade_no'],
                'out_refund_no' => $params['out_request_no'],
                'amount' => $params['amount'],
                'total_amount' => $params['payment_amount'] ?? $params['amount'],
                'provider_transaction_no' => $params['provider_transaction_no'] ?? '',
                'reason' => $params['reason'] ?? '退款',
                'notify_url' => $params['notify_url'] ?? '',
            ]);

            if (!($result['success'] ?? false)) {
                return RefundProviderResult::failed(
                    $result['message'] ?? '微信退款请求失败',
                    $result,
                );
            }

            // WeChat asynchronous: HTTP 200 means request ACCEPTED, not completed
            // Final status comes via callback.
            // Note: WeChat refund response does NOT include transaction_id.
            // provider_transaction_no comes from local payment record.
            return RefundProviderResult::processing(
                providerRefundNo: $result['refund_id'] ?? '',
                providerTransactionNo: $params['provider_transaction_no'] ?? '',
                amount: $params['amount'],
                raw: $result,
            );
        } catch (\Throwable $e) {
            // Network timeout → UNKNOWN, not FAILED
            Log::warning('微信退款调用异常，进入UNKNOWN状态', [
                'out_trade_no' => $params['out_trade_no'],
                'out_request_no' => $params['out_request_no'],
                'error' => $e->getMessage(),
            ]);
            return RefundProviderResult::unknown(
                '微信退款调用异常: ' . $e->getMessage(),
                ['exception' => get_class($e)],
            );
        }
    }

    public function query(string $outRequestNo, string $outTradeNo): RefundProviderResult
    {
        // WeChat refund query: GET /v3/refund/domestic/refunds/out-refund-no/{out_refund_no}
        // Not yet implemented in WechatPayService; return unknown for now
        // P1-PI-05 will implement actual query
        return RefundProviderResult::unknown(
            '微信退款查询接口尚未实现 (GET /v3/refund/domestic/refunds/out-refund-no)',
        );
    }

    public function verifyNotify(array $request): bool
    {
        // WeChat refund notify signature verification (V3)
        // P1-PI-04 will implement full verification with certificate
        if (!$this->isConfigured()) {
            return false;
        }
        return $this->wechatService->verifyNotify($request) ?? false;
    }

    public function parseNotify(array $request): RefundProviderResult
    {
        // Parse WeChat refund notification (V3 decrypted resource)
        // P1-PI-04 will implement full parsing
        $refundStatus = $request['refund_status'] ?? '';
        $successStatuses = ['SUCCESS', 'REFUND_SUCCESS'];

        if (in_array($refundStatus, $successStatuses, true)) {
            return RefundProviderResult::success(
                providerRefundNo: $request['refund_id'] ?? '',
                providerTransactionNo: $request['transaction_id'] ?? '',
                amount: isset($request['amount']['refund'])
                    ? number_format($request['amount']['refund'] / 100, 2, '.', '')
                    : '',
                raw: $request,
            );
        }

        if ($refundStatus === 'PROCESSING' || $refundStatus === 'REFUND_PROCESSING') {
            return RefundProviderResult::processing(
                providerRefundNo: $request['refund_id'] ?? '',
                providerTransactionNo: $request['transaction_id'] ?? '',
                amount: '',
                raw: $request,
            );
        }

        return RefundProviderResult::failed(
            '微信退款通知状态: ' . ($refundStatus ?: 'unknown'),
            $request,
        );
    }

    public function getProviderName(): string
    {
        return 'wechat';
    }

    public function isConfigured(): bool
    {
        return $this->wechatService->isConfigured() ?? false;
    }
}
