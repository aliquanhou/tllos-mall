<?php
namespace App\Modules\Marketing\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('coupons');
        if ($request->filled('keyword')) $query->where('name', 'like', '%' . $request->keyword . '%');
        if ($request->filled('type') && $request->type !== '') $query->where('type', $request->type);
        if ($request->filled('status') && $request->status !== '') $query->where('status', $request->status);
        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('sort', 'asc')->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)->limit($limit)->get();
        $stats = [
            'total' => DB::table('coupons')->count(),
            'active' => DB::table('coupons')->where('status', 1)->count(),
            'inactive' => DB::table('coupons')->where('status', 0)->count(),
            'total_received' => DB::table('coupon_users')->count(),
            'total_used' => DB::table('coupon_users')->where('status', 1)->count(),
        ];
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function show($id)
    {
        $coupon = DB::table('coupons')->where('id', $id)->first();
        if (!$coupon) return $this->error('优惠券不存在');
        return $this->success($coupon);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:1,2,3',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'total_count' => 'nullable|integer|min:0',
            'limit_per_user' => 'nullable|integer|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'valid_days' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|default:1',
            'is_new_user' => 'nullable|integer|default:0',
            'sort' => 'nullable|integer|default:0',
        ]);
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        $id = DB::table('coupons')->insertGetId($validated);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|in:1,2,3',
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'discount_rate' => 'sometimes|nullable|numeric|min:0|max:100',
            'min_amount' => 'sometimes|nullable|numeric|min:0',
            'total_count' => 'sometimes|nullable|integer|min:0',
            'limit_per_user' => 'sometimes|nullable|integer|min:1',
            'status' => 'sometimes|nullable|integer',
            'is_new_user' => 'sometimes|nullable|integer',
            'sort' => 'sometimes|nullable|integer',
        ]);
        $validated['updated_at'] = now();
        DB::table('coupons')->where('id', $id)->update($validated);
        return $this->success(null, '更新成功');
    }

    public function toggleStatus($id)
    {
        $coupon = DB::table('coupons')->where('id', $id)->first();
        if (!$coupon) return $this->error('优惠券不存在');
        $newStatus = $coupon->status == 1 ? 0 : 1;
        DB::table('coupons')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);
        return $this->success(['status' => $newStatus], $newStatus == 1 ? '已开启' : '已关闭');
    }

    public function destroy($id)
    {
        $hasReceived = DB::table('coupon_users')->where('coupon_id', $id)->exists();
        if ($hasReceived) return $this->error('该优惠券已有用户领取，无法删除');
        DB::table('coupons')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    public function records(Request $request)
    {
        $query = DB::table('coupon_users as cu')
            ->leftJoin('coupons as c', 'cu.coupon_id', '=', 'c.id')
            ->leftJoin('users as u', 'cu.user_id', '=', 'u.id')
            ->select('cu.*', 'c.name as coupon_name', 'c.type', 'c.discount_amount', 'c.discount_rate', 'u.nickname', 'u.mobile');
        if ($request->filled('coupon_id')) $query->where('cu.coupon_id', $request->coupon_id);
        if ($request->filled('status') && $request->status !== '') $query->where('cu.status', $request->status);
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('c.name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('u.nickname', 'like', '%' . $request->keyword . '%')
                  ->orWhere('u.mobile', 'like', '%' . $request->keyword . '%');
            });
        }
        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('cu.id', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
}
