<?php

namespace App\Modules\Refund\Constants;

/**
 * P1-REFUND-FOUNDATION-R1: Unified Refund Status Constants
 *
 * R1 Fix: Legacy status semantic collision resolved.
 *
 * OLD system semantics (must be preserved to avoid data migration):
 *   0 = pending review
 *   2 = rejected
 *   5 = refund success
 *   6 = cancelled
 *
 * NEW unified semantics (Option A: keep legacy numeric values, add new ones):
 *   0 = REQUESTED   (legacy: pending review — semantic match)
 *   1 = PROCESSING  (NEW: third-party accepted, awaiting final result)
 *   2 = REJECTED    (legacy: rejected — semantic match)
 *   3 = FAILED      (NEW: refund failed, may retry)
 *   4 = UNKNOWN     (NEW: timeout/ambiguous, manual reconciliation)
 *   5 = SUCCESS     (legacy: refund success — semantic match)
 *   6 = CANCELLED   (legacy: cancelled — semantic match)
 *
 * This ensures database status values have ONE unambiguous meaning,
 * and historical record id=2 (status=5) correctly means SUCCESS.
 */
class RefundStatus
{
    public const REQUESTED = 0;    // Pending review / initial request
    public const PROCESSING = 1;   // Third-party accepted, awaiting final result
    public const REJECTED = 2;     // Admin rejected (legacy value)
    public const FAILED = 3;       // Refund failed (may retry)
    public const UNKNOWN = 4;      // Timeout/ambiguous, requires manual reconciliation
    public const SUCCESS = 5;      // Refund completed (legacy value)
    public const CANCELLED = 6;    // User cancelled (legacy value)

    public const ALL = [
        self::REQUESTED,
        self::PROCESSING,
        self::REJECTED,
        self::FAILED,
        self::UNKNOWN,
        self::SUCCESS,
        self::CANCELLED,
    ];

    /**
     * Statuses that count toward locked refund amount.
     * REQUESTED + PROCESSING + SUCCESS all represent committed refund intent.
     */
    public const ACTIVE_REFUND_STATUSES = [
        self::REQUESTED,
        self::PROCESSING,
        self::UNKNOWN,
        self::SUCCESS,
    ];

    public const TERMINAL_STATUSES = [
        self::SUCCESS,
        self::CANCELLED,
        self::REJECTED,
    ];

    /**
     * States from which approveRefund() is allowed.
     * Only REQUESTED can move to PROCESSING.
     */
    public const APPROVAL_ALLOWED_FROM = [
        self::REQUESTED,
    ];

    public static function labels(): array
    {
        return [
            self::REQUESTED => '待处理',
            self::PROCESSING => '处理中',
            self::REJECTED => '已拒绝',
            self::FAILED => '退款失败',
            self::UNKNOWN => '未知/待对账',
            self::SUCCESS => '退款成功',
            self::CANCELLED => '已取消',
        ];
    }

    public static function label(int $status): string
    {
        return self::labels()[$status] ?? '未知状态';
    }

    /**
     * R1: Legacy status mapping verification.
     * Returns true if the given database status value has the same
     * meaning in both old and new systems (no semantic collision).
     */
    public static function isLegacyCompatible(int $status): bool
    {
        return in_array($status, [
            self::REQUESTED,  // 0: old=pending, new=requested — same
            self::REJECTED,   // 2: old=rejected, new=rejected — same
            self::SUCCESS,    // 5: old=success, new=success — same
            self::CANCELLED,  // 6: old=cancelled, new=cancelled — same
        ]);
    }
}
