<?php

namespace App\Modules\UserCenter\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Send a notification to a user.
     * Idempotent: same event_type + reference_type + reference_id only creates once.
     */
    public function send(int $userId, string $type, string $title, string $content, array $extra = [], ?string $referenceType = null, ?int $referenceId = null): bool
    {
        // Idempotency check - use LIKE for JSON field (MariaDB compatible)
        if ($referenceType && $referenceId) {
            $exists = DB::table('user_notifications')
                ->where('user_id', $userId)
                ->where('type', $type)
                ->where('extra', 'like', '%"reference_type":"' . $referenceType . '"%')
                ->where('extra', 'like', '%"reference_id":' . $referenceId . '%')
                ->exists();
            if ($exists) {
                return false; // Already sent
            }
        }

        return DB::table('user_notifications')->insert([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'is_read' => 0,
            'extra' => json_encode(array_merge($extra, [
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Payment success notification
     */
    public function sendPaymentSuccess(int $userId, string $orderNo, float $amount): bool
    {
        // Idempotency: check if already sent
        $exists = DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('type', 'PAYMENT_SUCCESS')
            ->where('content', 'like', '%' . $orderNo . '%')
            ->exists();
        if ($exists) return false;

        return DB::table('user_notifications')->insert([
            'user_id' => $userId,
            'type' => 'PAYMENT_SUCCESS',
            'title' => '支付成功',
            'content' => "您的订单 {$orderNo} 已支付成功，金额 ¥{$amount}",
            'is_read' => 0,
            'extra' => json_encode(['order_no' => $orderNo, 'amount' => $amount]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Refund success notification
     */
    public function sendRefundSuccess(int $userId, string $refundNo, float $amount): bool
    {
        // Idempotency: check if already sent
        $exists = DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('type', 'REFUND_SUCCESS')
            ->where('content', 'like', '%' . $refundNo . '%')
            ->exists();
        if ($exists) return false;

        return DB::table('user_notifications')->insert([
            'user_id' => $userId,
            'type' => 'REFUND_SUCCESS',
            'title' => '退款成功',
            'content' => "您的退款 {$refundNo} 已完成，金额 ¥{$amount}",
            'is_read' => 0,
            'extra' => json_encode(['refund_no' => $refundNo, 'amount' => $amount]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Order status change notification
     */
    public function sendOrderStatusChange(int $userId, string $orderNo, string $status, string $message): bool
    {
        return $this->send(
            $userId,
            'ORDER_' . strtoupper($status),
            "订单状态更新",
            "订单 {$orderNo}：{$message}",
            ['order_no' => $orderNo, 'status' => $status],
            'ORDER_STATUS',
            crc32($orderNo . '_' . $status)
        );
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        return DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * Mark as read
     */
    public function markRead(int $userId, int $notificationId): bool
    {
        return DB::table('user_notifications')
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['is_read' => 1, 'read_at' => now()]);
    }

    /**
     * Mark all as read
     */
    public function markAllRead(int $userId): bool
    {
        return DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => now()]);
    }
}
