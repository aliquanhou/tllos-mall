<?php
namespace App\Modules\Payment\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PaymentController extends BaseController
{
    public function pay(Request $request)
    {
        $request->validate(['order_id' => 'required|integer', 'pay_type' => 'required|integer|in:1,2,3']);
        $userId = $request->user()->id;
        $order = Order::where('id', $request->order_id)->where('user_id', $userId)->first();
        if (!$order) return $this->error('订单不存在', 404);
        if ($order->status != 0) return $this->error('订单状态不正确');

        $payTypeNames = [1 => '微信支付', 2 => '支付宝', 3 => '余额支付'];
        $payNo = 'PAY' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 1,
                'pay_type' => $request->pay_type,
                'pay_no' => $payNo,
                'pay_time' => Carbon::now(),
            ]);

            OrderLog::create([
                'order_id' => $order->id, 'order_no' => $order->order_no,
                'action' => 2, 'action_name' => '支付成功',
                'operator_type' => 'user', 'operator_id' => $userId,
                'remark' => "{$payTypeNames[$request->pay_type]} 支付成功，金额 ¥{$order->pay_amount}",
            ]);

            DB::table('payments')->insert([
                'payment_no' => $payNo,
                'order_no' => $order->order_no,
                'user_id' => $userId,
                'type' => 1,
                'pay_type' => $request->pay_type,
                'amount' => $order->pay_amount,
                'third_payment_no' => $payNo,
                'status' => 1,
                'pay_time' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 分销佣金自动计算
            $totalCommission = 0;
            $orderItems = DB::table('order_items')->where('order_id', $order->id)->get();
            foreach ($orderItems as $item) {
                $distributeGoods = DB::table('distribute_goods')->where('product_id', $item->product_id)->where('is_distribute', 1)->where('status', 1)->first();
                if ($distributeGoods) {
                    if ($distributeGoods->commission_type == 1) {
                        // 按比例
                        $commission = round($item->pay_amount * $distributeGoods->commission_rate / 100, 2);
                    } else {
                        // 固定金额
                        $commission = $distributeGoods->commission_amount * $item->quantity;
                    }
                    $totalCommission += $commission;

                    // 如果订单有关联分销商，创建分销订单记录
                    if ($order->agent_id > 0) {
                        $agent = DB::table('distribute_agents')->where('id', $order->agent_id)->where('status', 1)->first();
                        if ($agent) {
                            DB::table('distribute_orders')->insert([
                                'order_id' => $order->id,
                                'order_no' => $order->order_no,
                                'user_id' => $userId,
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
                            // 更新分销商累计佣金
                            DB::table('distribute_agents')->where('id', $order->agent_id)->increment('total_income', $commission);
                        }
                    }
                }
            }
            // 更新订单佣金字段
            if ($totalCommission > 0) {
                $order->update(['commission' => $totalCommission]);
            }

            DB::commit();
            return $this->success(['order_no' => $order->order_no, 'pay_amount' => $order->pay_amount], '支付成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('支付失败: ' . $e->getMessage());
        }
    }

    public function status($orderId, Request $request)
    {
        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->first();
        if (!$order) return $this->error('订单不存在', 404);
        return $this->success(['status' => $order->status, 'pay_type' => $order->pay_type, 'pay_time' => $order->pay_time]);
    }
}
