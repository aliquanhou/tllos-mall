<?php
namespace App\Modules\Payment\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Payment\Services\WechatPayService;
use App\Modules\Payment\Services\AlipayService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class PaymentController extends BaseController
{
    /**
     * 发起支付
     */
    public function pay(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'pay_type' => 'required|integer|in:1,2,3,4',
        ]);

        $userId = $request->user()->id;
        $order = Order::where('id', $request->order_id)->where('user_id', $userId)->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if ($order->status != 0) {
            return $this->error('订单状态不正确，当前状态: ' . $order->status);
        }

        $payNo = 'PAY' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $payTypeNames = [1 => '微信支付', 2 => '支付宝', 3 => '余额支付', 4 => '积分支付'];

        // 余额支付
        if ($request->pay_type == 3) {
            return $this->balancePay($order, $userId, $payNo);
        }

        // 积分支付
        if ($request->pay_type == 4) {
            return $this->pointPay($order, $userId, $payNo);
        }

        // 实例化支付服务并检查配置（生产环境配置不完整则 Fail-Closed）
        if ($request->pay_type == 1) {
            $service = new WechatPayService();
        } else {
            $service = new AlipayService();
        }

        if (config('app.env') === 'production' && !$service->isConfigured()) {
            Log::warning('支付请求被拒绝：生产环境支付配置不完整', [
                'pay_type' => $request->pay_type,
                'order_no' => $order->order_no,
                'user_id' => $userId,
            ]);
            return $this->error($payTypeNames[$request->pay_type] . '暂未配置完成，请使用其他支付方式');
        }

        // 创建支付记录（待支付状态）
        DB::table('payments')->insert([
            'payment_no' => $payNo,
            'order_no' => $order->order_no,
            'user_id' => $userId,
            'type' => 1,
            'pay_type' => $request->pay_type,
            'amount' => $order->pay_amount,
            'status' => 0,
            'provider' => $request->pay_type == 1 ? 'wechat' : ($request->pay_type == 2 ? 'alipay' : ($request->pay_type == 3 ? 'balance' : 'points')),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 调用第三方支付下单
        $params = [
            'out_trade_no' => $payNo,
            'amount' => $order->pay_amount,
            'description' => '订单支付-' . $order->order_no,
            'notify_url' => config('app.url') . '/api/v1/payment/notify/' . ($request->pay_type == 1 ? 'wechat' : 'alipay'),
        ];

        $result = $service->unifiedOrder($params);

        if (!$result['success']) {
            return $this->error('支付下单失败: ' . ($result['message'] ?? '未知错误'));
        }

        // 沙箱模式下直接支付成功
        if ($service->isSandbox()) {
            $this->processMockPaySuccess($order, $userId, $payNo, $request->pay_type, $payTypeNames[$request->pay_type], $result);
            return $this->success([
                'order_no' => $order->order_no,
                'pay_amount' => $order->pay_amount,
                'pay_type' => $request->pay_type,
                'sandbox' => true,
                'message' => '沙箱模式支付成功',
            ], '支付成功');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'pay_amount' => $order->pay_amount,
            'pay_type' => $request->pay_type,
            'pay_params' => $result,
        ], '支付下单成功');
    }

    /**
     * 余额支付
     */
    private function balancePay($order, $userId, $payNo)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || $user->balance < $order->pay_amount) {
            return $this->error('余额不足，请充值');
        }

        DB::beginTransaction();
        try {
            $beforeBalance = $user->balance;
            DB::table('users')->where('id', $userId)->decrement('balance', $order->pay_amount);

            $order->update([
                'status' => 1,
                'pay_type' => 3,
                'pay_no' => $payNo,
                'pay_time' => Carbon::now(),
            ]);

            OrderLog::create([
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'action' => 2,
                'action_name' => '支付成功',
                'operator_type' => 'user',
                'operator_id' => $userId,
                'remark' => '余额支付成功，金额 ¥' . $order->pay_amount,
            ]);

            DB::table('payments')->insert([
                'payment_no' => $payNo,
                'order_no' => $order->order_no,
                'user_id' => $userId,
                'type' => 1,
                'pay_type' => 3,
                'amount' => $order->pay_amount,
                'third_payment_no' => $payNo,
                'status' => 1,
                'pay_time' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 余额变动日志
            DB::table('user_account_logs')->insert([
                'user_id' => $userId,
                'type' => 2,
                'amount' => $order->pay_amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $beforeBalance - $order->pay_amount,
                'remark' => '订单支付-' . $order->order_no,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 分销佣金
            $this->calculateCommission($order);

            DB::commit();
            return $this->success(['order_no' => $order->order_no, 'pay_amount' => $order->pay_amount], '支付成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('支付失败: ' . $e->getMessage());
        }
    }

    /**
     * 积分支付
     */
    private function pointPay($order, $userId, $payNo)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        $pointsNeeded = round($order->pay_amount, 1); // 1积分=1元，精度0.1

        if (!$user || $user->points < $pointsNeeded) {
            return $this->error('积分不足，当前积分: ' . ($user->points ?? 0) . '，需要: ' . $pointsNeeded);
        }

        DB::beginTransaction();
        try {
            $beforePoints = $user->points;

            // 扣减积分
            DB::table('users')->where('id', $userId)->decrement('points', $pointsNeeded);

            // 更新订单状态
            $order->update([
                'status' => 1,
                'pay_type' => 4,
                'pay_no' => $payNo,
                'pay_time' => Carbon::now(),
            ]);

            // 记录订单日志
            OrderLog::create([
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'action' => 2,
                'action_name' => '支付成功',
                'operator_type' => 'user',
                'operator_id' => $userId,
                'remark' => '积分支付成功，消耗' . $pointsNeeded . '积分，金额 ¥' . $order->pay_amount,
            ]);

            // 记录支付记录
            DB::table('payments')->insert([
                'payment_no' => $payNo,
                'order_no' => $order->order_no,
                'user_id' => $userId,
                'type' => 1,
                'pay_type' => 4,
                'amount' => $order->pay_amount,
                'third_payment_no' => $payNo,
                'status' => 1,
                'pay_time' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 记录积分消费流水
            DB::table('user_point_logs')->insert([
                'user_id' => $userId,
                'points' => -$pointsNeeded,
                'type' => 'spend',
                'description' => '订单支付消耗' . $pointsNeeded . '积分，订单号：' . $order->order_no,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 分销佣金
            $this->calculateCommission($order);

            DB::commit();
            return $this->success([
                'order_no' => $order->order_no,
                'pay_amount' => $order->pay_amount,
                'points_spent' => $pointsNeeded,
                'balance' => $beforePoints - $pointsNeeded,
            ], '支付成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('支付失败: ' . $e->getMessage());
        }
    }

    /**
     * 沙箱模式支付成功处理
     * P1-C: 精确绑定当前 payment_no，不批量更新同订单其他 payment
     */
    private function processMockPaySuccess($order, $userId, $payNo, $payType, $payTypeName, $payResult)
    {
        DB::beginTransaction();
        try {
            $order->update([
                'status' => 1,
                'pay_type' => $payType,
                'pay_no' => $payResult['transaction_id'] ?? $payNo,
                'pay_time' => Carbon::now(),
            ]);

            OrderLog::create([
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'action' => 2,
                'action_name' => '支付成功',
                'operator_type' => 'user',
                'operator_id' => $userId,
                'remark' => "{$payTypeName}支付成功（沙箱），金额 ¥{$order->pay_amount}",
            ]);

            // P1-C: 只更新当前 payment_no 对应的记录，不影响同订单其他待支付记录
            DB::table('payments')->where('payment_no', $payNo)->update([
                'third_payment_no' => $payResult['transaction_id'] ?? $payNo,
                'status' => 1,
                'pay_time' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $this->calculateCommission($order);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('沙箱支付处理失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 计算分销佣金
     */
    private function calculateCommission($order)
    {
        $totalCommission = 0;
        $orderItems = DB::table('order_items')->where('order_id', $order->id)->get();

        foreach ($orderItems as $item) {
            $distributeGoods = DB::table('distribute_goods')
                ->where('product_id', $item->product_id)
                ->where('is_distribute', 1)
                ->where('status', 1)
                ->first();

            if ($distributeGoods) {
                $commission = $distributeGoods->commission_type == 1
                    ? round($item->pay_amount * $distributeGoods->commission_rate / 100, 2)
                    : $distributeGoods->commission_amount * $item->quantity;
                $totalCommission += $commission;

                if ($order->agent_id > 0) {
                    $agent = DB::table('distribute_agents')->where('id', $order->agent_id)->where('status', 1)->first();
                    if ($agent) {
                        DB::table('distribute_orders')->insert([
                            'order_id' => $order->id,
                            'order_no' => $order->order_no,
                            'user_id' => $order->user_id,
                            'agent_id' => $order->agent_id,
                            'level_id' => $agent->level_id,
                            'goods_amount' => $item->pay_amount,
                            'commission_rate' => $distributeGoods->commission_type == 1 ? $distributeGoods->commission_rate : 0,
                            'commission_amount' => $commission,
                            'commission' => $commission,
                            'status' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        DB::table('distribute_agents')->where('id', $order->agent_id)->increment('total_income', $commission);
                    }
                }
            }
        }

        if ($totalCommission > 0) {
            $order->update(['commission' => $totalCommission]);
        }
    }

    /**
     * 查询支付状态
     */
    public function status($orderId, Request $request)
    {
        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->first();
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        return $this->success([
            'status' => $order->status,
            'pay_type' => $order->pay_type,
            'pay_time' => $order->pay_time,
            'pay_amount' => $order->pay_amount,
        ]);
    }

    /**
     * 支付方式列表
     * P1-A: 生产环境过滤掉未配置完成的支付方式
     */
    public function methods()
    {
        $methods = DB::table('pay_configs')->where('status', 1)->orderBy('sort', 'asc')->get();
        $list = [];
        foreach ($methods as $m) {
            $config = $m->config ? json_decode($m->config, true) : [];
            $isConfigured = !empty($config);
            if ($m->code === 'wechat') {
                $required = ['app_id', 'mch_id', 'api_v3_key', 'serial_no', 'private_key'];
                $isConfigured = true;
                foreach ($required as $key) {
                    if (empty($config[$key])) { $isConfigured = false; break; }
                }
            } elseif ($m->code === 'alipay') {
                $required = ['app_id', 'merchant_private_key', 'alipay_public_key'];
                $isConfigured = true;
                foreach ($required as $key) {
                    if (empty($config[$key])) { $isConfigured = false; break; }
                }
            }
            // 生产环境：未配置完成的支付方式不返回给前端
            if (config('app.env') === 'production' && !$isConfigured) {
                continue;
            }
            $list[] = [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'sort' => $m->sort,
                'configured' => $isConfigured,
            ];
        }
        return $this->success(['list' => $list]);
    }
}
