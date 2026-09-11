<?php

namespace App\Modules\Refund\Constants;

/**
 * P1-REFUND-FOUNDATION: Unified Refund Status Constants
 *
 * Status machine:
 *   REQUESTED  → PROCESSING → SUCCESS
 *   REQUESTED  → PROCESSING → FAILED (retryable)
 *   REQUESTED  → CANCELLED (user cancels before processing)
 *   REQUESTED  → REJECTED (admin rejects)
 *   PROCESSING → UNKNOWN (timeout/ambiguous, manual reconciliation)
 *
 * Forbidden transitions:
 *   SUCCESS → PROCESSING
 *   SUCCESS → FAILED
 *   CANCELLED → * (terminal)
 *   REJECTED → * (terminal, unless reopened by admin)
 */
class RefundStatus
{
    public const REQUESTED = 0;    // Pending review / initial request
    public const PROCESSING = 1;   // Third-party accepted, awaiting final result
    public const SUCCESS = 2;      // Refund completed (verified by callback/query)
    public const FAILED = 3;       // Refund failed (may retry)
    public const CANCELLED = 4;    // User cancelled
    public const UNKNOWN = 5;      // Timeout/ambiguous, requires manual reconciliation
    public const REJECTED = 6;     // Admin rejected

    public const ALL = [
        self::REQUESTED,
        self::PROCESSING,
        self::SUCCESS,
        self::FAILED,
        self::CANCELLED,
        self::UNKNOWN,
        self::REJECTED,
    ];

    public const ACTIVE_REFUND_STATUSES = [
        self::REQUESTED,
        self::PROCESSING,
        self::SUCCESS,
    ];

    public const TERMINAL_STATUSES = [
        self::SUCCESS,
        self::CANCELLED,
        self::REJECTED,
    ];

    public static function labels(): array
    {
        return [
            self::REQUESTED => '待处理',
            self::PROCESSING => '处理中',
            self::SUCCESS => '退款成功',
            self::FAILED => '退款失败',
            self::CANCELLED => '已取消',
            self::UNKNOWN => '未知/待对账',
            self::REJECTED => '已拒绝',
        ];
    }

    public static function label(int $status): string
    {
        return self::labels()[$status] ?? '未知状态';
    }
}
