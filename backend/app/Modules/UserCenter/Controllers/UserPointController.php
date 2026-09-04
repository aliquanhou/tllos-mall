<?php
namespace App\Modules\UserCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PointService;

class UserPointController extends BaseController
{
    public function sign(Request $request)
    {
        $userId = $request->user()->id;
        $result = PointService::grantPoints($userId, 'sign');
        if ($result['success']) {
            $user = DB::table('users')->where('id', $userId)->first();
            return $this->success(['points' => $result['points'], 'total_points' => $user->points], $result['message']);
        }
        return $this->error($result['message']);
    }

    public function share(Request $request)
    {
        $userId = $request->user()->id;
        $v = $request->validate(['product_id' => 'nullable|integer']);
        $result = PointService::grantPoints($userId, 'share', $v['product_id'] ?? null);
        if ($result['success']) {
            $user = DB::table('users')->where('id', $userId)->first();
            return $this->success(['points' => $result['points'], 'total_points' => $user->points], $result['message']);
        }
        return $this->error($result['message']);
    }

    public function myPoints(Request $request)
    {
        $userId = $request->user()->id;
        $user = DB::table('users')->where('id', $userId)->first();
        $logs = DB::table('user_point_logs')->where('user_id', $userId)->orderBy('id', 'desc')->limit(20)->get();
        $todaySigned = DB::table('user_point_logs')->where('user_id', $userId)->where('type', 'sign')->whereDate('created_at', date('Y-m-d'))->exists();
        return $this->success(['points' => $user->points, 'today_signed' => $todaySigned, 'logs' => $logs]);
    }
}
