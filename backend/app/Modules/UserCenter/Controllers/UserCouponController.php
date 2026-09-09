<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserCouponController extends BaseController {
    public function lists(Request $request) {
        $userId = $request->user()->id;
        $status = $request->get('status');
        $query = DB::table('user_coupons')->where('user_id', $userId);
        if ($status !== null) $query->where('status', $status);
        $list = $query->orderBy('id', 'desc')->get();
        return $this->success($list);
    }
    public function receive(Request $request) {
        $userId = $request->user()->id;
        $couponId = $request->input('coupon_id');
        $coupon = DB::table('coupons')->where('id', $couponId)->first();
        if (!$coupon) return $this->error('优惠券不存在');
        $exists = DB::table('user_coupons')->where('user_id', $userId)->where('coupon_id', $couponId)->exists();
        if ($exists) return $this->error('已领取');
        DB::table('user_coupons')->insert([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'coupon_name' => $coupon->name,
            'min_amount' => $coupon->min_amount,
            'discount_amount' => $coupon->discount_amount,
            'discount_type' => $coupon->type,
            'discount_rate' => $coupon->discount_rate,
            'start_time' => now(),
            'end_time' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'status' => 0,
            
            'created_at' => now(),
        ]);
        DB::table('coupons')->where('id', $couponId)->increment('receive_count');
        return $this->success(null, '领取成功');
    }
}
