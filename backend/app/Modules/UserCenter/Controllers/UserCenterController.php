<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserCenterController extends BaseController {
    public function center(Request $request) {
        $user = $request->user();
        $userId = $user->id;
        $orderCount = DB::table('orders')->where('user_id',$userId)->count();
        $couponCount = DB::table('user_coupons')->where('user_id',$userId)->where('status',0)->count();
        $collectCount = DB::table('goods_collects')->where('user_id',$userId)->count();
        $balance = DB::table('user_balances')->where('user_id',$userId)->value('balance') ?? 0;
        $points = DB::table('user_balances')->where('user_id',$userId)->value('points') ?? 0;
        return $this->success([
            'user_info' => $user,
            'order_count' => $orderCount,
            'coupon_count' => $couponCount,
            'collect_count' => $collectCount,
            'balance' => $balance,
            'points' => $points,
        ]);
    }
    public function info(Request $request) {
        return $this->success($request->user());
    }
    public function updateInfo(Request $request) {
        $userId = $request->user()->id;
        $data = $request->only(['nickname','avatar','gender']);
        DB::table('users')->where('id',$userId)->update($data);
        return $this->success(null,'修改成功');
    }
}
