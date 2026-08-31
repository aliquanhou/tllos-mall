<?php
namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends BaseController
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->keyword) {
            $query->where(function($q) use ($request) {
                $q->where('nickname', 'like', "%{$request->keyword}%")
                  ->orWhere('mobile', 'like', "%{$request->keyword}%")
                  ->orWhere('email', 'like', "%{$request->keyword}%");
            });
        }
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        if ($request->start_time) $query->where('created_at', '>=', $request->start_time);
        if ($request->end_time) $query->where('created_at', '<=', $request->end_time);

        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?: 20);
        $stats = [
            'total' => User::count(),
            'today' => User::whereDate('created_at', today())->count(),
            'active' => User::where('status', 1)->count(),
            'disabled' => User::where('status', 0)->count(),
        ];
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'stats' => $stats]);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) return $this->error('用户不存在', 404);
        $orders = DB::table('orders')->where('user_id', $id)->count();
        $orderAmount = DB::table('orders')->where('user_id', $id)->where('status', '>=', 1)->sum('pay_amount');
        $balance = DB::table('user_balances')->where('user_id', $id)->first();
        return $this->success(array_merge($user->toArray(), [
            'order_count' => $orders,
            'order_amount' => $orderAmount,
            'balance' => $balance->balance ?? 0,
            'points' => $balance->points ?? 0,
        ]));
    }

    public function toggleStatus($id)
    {
        $user = User::find($id);
        if (!$user) return $this->error('用户不存在', 404);
        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();
        return $this->success(['status' => $user->status], $user->status == 1 ? '已启用' : '已禁用');
    }

    public function update($id, Request $request)
    {
        $user = User::find($id);
        if (!$user) return $this->error('用户不存在', 404);
        $data = $request->only(['nickname', 'avatar', 'mobile', 'email', 'gender', 'status']);
        $user->update($data);
        return $this->success($user, '更新成功');
    }
}
