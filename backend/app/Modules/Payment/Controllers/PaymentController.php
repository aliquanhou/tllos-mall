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
            'pay_type' => 'required|integer|in:1,2,3',
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
        $payTypeNames = [1 => '微信支付', 2 => '支付宝', 3 => '余额支付'];

        // 余额支付
        if ($request->pay_type == 3) {
            return $this->balancePay($order, $userId, $payNo);
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
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 调用第三方支付下单
        $params = [
            'out_trade_no' => $order->order_no,
            'amount' => $order->pay_amount,
            'description' => '订单支付-' . $order->order_no,
            'notify_url' => config('app.url') . '/api/v1/payment/notify/' . ($request->pay_type == 1 ? 'wechat' : 'alipay'),
        ];

        if ($request->pay_type == 1) {
            $service = new WechatPayService();
            $result = $service->unifiedOrder($params);
        } else {
            $service = new AlipayService();
            $result = $service->unifiedOrder($params);
        }

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
     * 沙箱模式支付成功处理
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

            DB::table('payments')->where('order_no', $order->order_no)->update([
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
     */
    public function methods()
    {
        $methods = DB::table('pay_configs')->where('status', 1)->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $methods]);
    }
}
