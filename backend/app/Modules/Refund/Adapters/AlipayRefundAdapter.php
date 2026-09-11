<?php

namespace App\Modules\Refund\Adapters;

use App\Modules\Refund\Contracts\RefundProviderInterface;
use App\Modules\Refund\Contracts\RefundProviderResult;
use App\Modules\Payment\Services\AlipayService;
use Illuminate\Support\Facades\Log;

/**
 * P1-PI-01: Alipay Refund Adapter
 *
 * Wraps existing AlipayService::refund() and normalizes the result
 * into RefundProviderResult.
 *
 * Alipay refund API (alipay.trade.refund) is SYNCHRONOUS:
 *   - Request: out_trade_no, out_request_no, refund_amount, refund_reason
 *   - Response code=10000 → refund completed immediately
 *   - Response: trade_no (Alipay transaction ID), out_request_no (merchant refund no)
 *
 * IMPORTANT: Alipay refund does NOT have an independent refund ID.
 * The refund is identified by (out_trade_no + out_request_no).
 * provider_refund_no = out_request_no (= TLL refund_no) is the canonical identity.
 *
 * Field mapping:
 *   TLL payment_no              → Alipay out_trade_no
 *   TLL refund_no               → Alipay out_request_no (idempotency key)
 *   Alipay trade_no             → TLL payments.third_payment_no
 *   Alipay response out_request_no → TLL order_refunds.provider_refund_no
 */
class AlipayRefundAdapter implements RefundProviderInterface
{
    public function __construct(
        private readonly AlipayService $alipayService,
    ) {}

    public function refund(array $params): RefundProviderResult
    {
        try {
            $result = $this->alipayService->refund([
                'out_trade_no' => $params['out_trade_no'],
                'out_refund_no' => $params['out_request_no'],
                'amount' => $params['amount'],
                'reason' => $params['reason'] ?? '退款',
            ]);

            if (!($result['success'] ?? false)) {
                return RefundProviderResult::failed(
                    $result['message'] ?? '支付宝退款失败',
                    $result,
                );
            }

            // Alipay synchronous: success means refund completed
            return RefundProviderResult::success(
                providerRefundNo: $result['out_refund_no'] ?? $params['out_request_no'],
                providerTransactionNo: $result['refund_id'] ?? '',
                amount: $params['amount'],
                raw: $result,
            );
        } catch (\Throwable $e) {
            // Network timeout / connection error → UNKNOWN, not FAILED
            // Provider may have received the request even if we didn't get a response
            Log::warning('支付宝退款调用异常，进入UNKNOWN状态', [
                'out_trade_no' => $params['out_trade_no'],
                'out_request_no' => $params['out_request_no'],
                'error' => $e->getMessage(),
            ]);
            return RefundProviderResult::unknown(
                '支付宝退款调用异常: ' . $e->getMessage(),
                ['exception' => get_class($e)],
            );
        }
    }

    public function query(string $outRequestNo, string $outTradeNo): RefundProviderResult
    {
        // Alipay refund query: alipay.trade.fastpay.refund.query
        // Not yet implemented in AlipayService; return unknown for now
        // P1-PI-05 will implement actual query
        return RefundProviderResult::unknown(
            '支付宝退款查询接口尚未实现 (alipay.trade.fastpay.refund.query)',
        );
    }

    public function verifyNotify(array $request): bool
    {
        // Alipay refund notify signature verification
        // Alipay refund is synchronous, but notify may still be sent
        // P1-PI-04 will implement actual verification
        if (!($this->alipayService->isConfigured() ?? false)) {
            return false;
        }
        // Delegate to AlipayService signature verification
        return $this->alipayService->verifyNotify($request) ?? false;
    }

    public function parseNotify(array $request): RefundProviderResult
    {
        // Parse Alipay refund notification
        // P1-PI-04 will implement full parsing
        $refundStatus = $request['refund_status'] ?? '';

        if ($refundStatus === 'REFUND_SUCCESS') {
            return RefundProviderResult::success(
                providerRefundNo: $request['out_request_no'] ?? '',
                providerTransactionNo: $request['trade_no'] ?? '',
                amount: $request['refund_amount'] ?? '',
                raw: $request,
            );
        }

        return RefundProviderResult::failed(
            '支付宝退款通知状态: ' . ($refundStatus ?: 'unknown'),
            $request,
        );
    }

    public function getProviderName(): string
    {
        return 'alipay';
    }

    public function isConfigured(): bool
    {
        return $this->alipayService->isConfigured() ?? false;
    }
}
