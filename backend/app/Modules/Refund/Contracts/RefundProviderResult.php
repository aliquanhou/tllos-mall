<?php

namespace App\Modules\Refund\Contracts;

/**
 * P1-PI-01: Normalized refund provider result DTO.
 *
 * All provider adapters return this unified result, so RefundService
 * doesn't need to know about Alipay/WeChat specific response formats.
 *
 * Status semantics:
 *   success    - refund completed (money returned to customer)
 *   processing - refund accepted, final result pending (async callback)
 *   failed     - refund explicitly failed by provider
 *   unknown    - timeout / uncertain, must query to resolve
 */
class RefundProviderResult
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOWN = 'unknown';

    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly string $providerRefundNo = '',
        public readonly string $providerTransactionNo = '',
        public readonly string $amount = '',
        public readonly string $message = '',
        public readonly array $raw = [],
    ) {}

    /**
     * Create a success result.
     */
    public static function success(
        string $providerRefundNo,
        string $providerTransactionNo,
        string $amount,
        array $raw = [],
    ): self {
        return new self(
            success: true,
            status: self::STATUS_SUCCESS,
            providerRefundNo: $providerRefundNo,
            providerTransactionNo: $providerTransactionNo,
            amount: $amount,
            raw: $raw,
        );
    }

    /**
     * Create a processing result (async provider accepted the request).
     */
    public static function processing(
        string $providerRefundNo,
        string $providerTransactionNo,
        string $amount,
        array $raw = [],
    ): self {
        return new self(
            success: true,
            status: self::STATUS_PROCESSING,
            providerRefundNo: $providerRefundNo,
            providerTransactionNo: $providerTransactionNo,
            amount: $amount,
            raw: $raw,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failed(string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: self::STATUS_FAILED,
            message: $message,
            raw: $raw,
        );
    }

    /**
     * Create an unknown result (timeout / uncertain delivery).
     * Must be resolved via query, NOT treated as failed.
     */
    public static function unknown(string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: self::STATUS_UNKNOWN,
            message: $message,
            raw: $raw,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isUnknown(): bool
    {
        return $this->status === self::STATUS_UNKNOWN;
    }
}
