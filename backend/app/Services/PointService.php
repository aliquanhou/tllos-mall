<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class PointService
{
    public static function grantPoints($userId, $type, $relatedId = null, $extraPoints = 0)
    {
        $rule = DB::table('point_rules')->where('type', $type)->where('status', 1)->first();
        if (!$rule) return false;

        $points = $rule->points;
        $description = $rule->description;

        if ($type == 'order' && $extraPoints > 0) {
            $points = $extraPoints;
            $description = "订单消费获得{$points}积分";
        }

        if ($type == 'sign') {
            $todaySigned = DB::table('user_point_logs')
                ->where('user_id', $userId)
                ->where('type', 'sign')
                ->whereDate('created_at', date('Y-m-d'))
                ->exists();
            if ($todaySigned) return ['success' => false, 'message' => '今日已签到'];
        }

        DB::beginTransaction();
        try {
            DB::table('user_point_logs')->insert([
                'user_id' => $userId,
                'points' => $points,
                'type' => $type,
                'description' => $description,
                'created_at' => now(),
            ]);
            DB::table('users')->where('id', $userId)->increment('points', $points);

            $now = now();
            if ($type == 'sign') {
                $title = '签到积分到账通知';
                $content = "恭喜您！每日签到获得{$points}积分。";
            } elseif ($type == 'order') {
                $title = '订单消费积分到账通知';
                $content = "恭喜您！订单消费获得{$points}积分。";
            } elseif ($type == 'comment') {
                $title = '评价积分到账通知';
                $content = "恭喜您！商品评价获得{$points}积分。";
            } elseif ($type == 'share') {
                $title = '分享积分到账通知';
                $content = "恭喜您！分享商品获得{$points}积分。";
            } else {
                $title = '积分变动通知';
                $content = "您的积分变动{$points}分。";
            }
            DB::table('user_notifications')->insert([
                'user_id' => $userId,
                'title' => $title,
                'content' => $content,
                'type' => 'system',
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();
            return ['success' => true, 'points' => $points, 'message' => "获得{$points}积分"];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
