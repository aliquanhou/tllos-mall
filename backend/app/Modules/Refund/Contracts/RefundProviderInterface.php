<?php

namespace App\Modules\Refund\Contracts;

/**
 * P1-PI-01: Refund Provider Interface
 *
 * Unified contract for all refund providers (Alipay, WeChat, etc.).
 * RefundService depends on this interface, not on concrete provider classes.
 *
 * Architecture:
 *   RefundService → RefundProviderInterface → AlipayRefundAdapter
 *                                    → WechatRefundAdapter
 *
 * CRITICAL: Provider calls MUST NOT be inside a database transaction.
 * Flow:
 *   DB transaction → commit local intent (REQUESTED/PROCESSING)
 *   → Provider API call (outside transaction)
 *   → persist result (SUCCESS/FAILED/UNKNOWN)
 */
interface RefundProviderInterface
{
    /**
     * Initiate a refund request to the provider.
     *
     * @param array{
     *   out_trade_no: string,      // TLL payment_no
     *   out_request_no: string,    // TLL refund_no (idempotency key)
     *   amount: string,            // refund amount in yuan, e.g. "50.00"
     *   reason: string,            // refund reason
     *   notify_url: string         // callback URL for async providers
     * } $params
     * @return RefundProviderResult
     */
    public function refund(array $params): RefundProviderResult;

    /**
     * Query refund status from provider.
     * Used for UNKNOWN recovery (timeout → query → SUCCESS/FAILED).
     *
     * @param string $outRequestNo  TLL refund_no
     * @param string $outTradeNo   TLL payment_no
     * @return RefundProviderResult
     */
    public function query(string $outRequestNo, string $outTradeNo): RefundProviderResult;

    /**
     * Verify provider callback signature.
     *
     * @param array $request  The full callback request data
     * @return bool  true if signature valid
     */
    public function verifyNotify(array $request): bool;

    /**
     * Parse provider callback into a normalized result.
     *
     * @param array $request
     * @return RefundProviderResult
     */
    public function parseNotify(array $request): RefundProviderResult;

    /**
     * Get provider name (alipay / wechat / etc.)
     */
    public function getProviderName(): string;

    /**
     * Whether this provider is fully configured for production use.
     * Production + incomplete config must FAIL CLOSED, not fallback to mock.
     */
    public function isConfigured(): bool;
}
