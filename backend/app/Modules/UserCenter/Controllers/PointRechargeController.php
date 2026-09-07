<?php
namespace App\Modules\UserCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PointRechargeController extends BaseController
{
    /**
     * 积分充值（模拟支付）
     */
    public function recharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $userId = $request->user()->id;
        $amount = round($request->amount, 1);
        $rechargeNo = 'PT' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // 记录充值流水
            DB::table('user_point_logs')->insert([
                'user_id' => $userId,
                'points' => $amount,
                'type' => 'recharge',
                'description' => "积分充值{$amount}分（模拟支付），流水号：{$rechargeNo}",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 增加用户积分
            DB::table('users')->where('id', $userId)->increment('points', $amount);

            // 发送通知（暂不启用，user_notifications表结构待完善）
            // DB::table('user_notifications')->insert([...]);

            DB::commit();

            $user = DB::table('users')->where('id', $userId)->first();
            return $this->success([
                'recharge_no' => $rechargeNo,
                'amount' => $amount,
                'balance' => $user->points,
            ], '充值成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('充值失败: ' . $e->getMessage());
        }
    }

    /**
     * 积分明细（完整流水）
     */
    public function logs(Request $request)
    {
        $userId = $request->user()->id;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $type = $request->get('type');

        $query = DB::table('user_point_logs')->where('user_id', $userId);
        if ($type) {
            $query->where('type', $type);
        }

        $total = $query->count();
        $logs = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $user = DB::table('users')->where('id', $userId)->first();

        return $this->success([
            'balance' => $user->points,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $logs,
        ]);
    }

    /**
     * 积分规则列表
     */
    public function rules()
    {
        $rules = DB::table('point_rules')->where('status', 1)->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $rules]);
    }
}
