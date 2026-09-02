<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LevelController extends BaseController {
    // 等级列表
    public function index(Request $request) {
        $list = DB::table('user_levels')->where('status',1)->orderBy('level','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }

    // 用户端升级进度
    public function progress(Request $request) {
        $user = $request->user();
        $currentPoints = $user->points ?? 0;
        $currentLevelId = $user->level_id ?? 1;

        $levels = DB::table('user_levels')->where('status',1)->orderBy('level','asc')->get();
        $currentLevel = $levels->firstWhere('id', $currentLevelId) ?? $levels->first();

        // 找下一级
        $nextLevel = null;
        foreach ($levels as $lv) {
            if ($lv->level > $currentLevel->level) {
                $nextLevel = $lv;
                break;
            }
        }

        $progressPercent = 0;
        $pointsNeeded = 0;
        if ($nextLevel) {
            $levelStart = $currentLevel->upgrade_points ?? 0;
            $levelEnd = $nextLevel->upgrade_points;
            $range = $levelEnd - $levelStart;
            if ($range > 0) {
                $progressPercent = min(100, round(($currentPoints - $levelStart) / $range * 100));
            }
            $pointsNeeded = max(0, $levelEnd - $currentPoints);
        } else {
            $progressPercent = 100;
        }

        // 等级变更日志（最近5条）
        $logs = DB::table('user_level_logs')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return $this->success([
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'current_points' => $currentPoints,
            'progress_percent' => $progressPercent,
            'points_needed' => $pointsNeeded,
            'level_logs' => $logs,
        ]);
    }

    // 用户通知列表
    public function notifications(Request $request) {
        $user = $request->user();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $type = $request->input('type');
        $isRead = $request->input('is_read');

        $query = DB::table('user_notifications')->where('user_id', $user->id);
        if ($type) $query->where('type', $type);
        if ($isRead !== null) $query->where('is_read', $isRead);

        $total = $query->count();
        $list = $query->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit)->get();

        $unreadCount = DB::table('user_notifications')->where('user_id', $user->id)->where('is_read', 0)->count();

        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'unread_count'=>$unreadCount]);
    }

    // 标记通知已读
    public function readNotification(Request $request, $id) {
        $user = $request->user();
        DB::table('user_notifications')->where('id', $id)->where('user_id', $user->id)->update([
            'is_read' => 1,
            'read_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->success(null, '已标记为已读');
    }

    // 全部标记已读
    public function readAllNotifications(Request $request) {
        $user = $request->user();
        DB::table('user_notifications')->where('user_id', $user->id)->where('is_read', 0)->update([
            'is_read' => 1,
            'read_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->success(null, '全部已读');
    }
}
