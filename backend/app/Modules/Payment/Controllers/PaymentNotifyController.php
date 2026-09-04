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

class PaymentNotifyController extends BaseController
{
    /**
     * 微信支付回调
     */
    public function wechat(Request $request)
    {
        Log::info('微信支付回调', $request->all());

        $service = new WechatPayService();
        $data = $request->all();
        // 沙箱模式下从POST数据读取
        if ($service->isSandbox()) {
            $data = $request->all();
        } else {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        $result = $service->verifyNotify($data);

        if (!$result['success']) {
            Log::error('微信支付回调验签失败', $result);
            return response('FAIL', 500);
        }

        $this->processPaymentSuccess($result['out_trade_no'], $result['transaction_id'], $result['amount'], 1);

        return response('SUCCESS', 200);
    }

    /**
     * 支付宝回调
     */
    public function alipay(Request $request)
    {
        Log::info('支付宝支付回调', $request->all());

        $service = new AlipayService();
        $result = $service->verifyNotify($request->all());

        if (!$result['success']) {
            Log::error('支付宝回调验签失败', $result);
            return 'fail';
        }

        // 交易状态判断
        if ($service->isSandbox() || ($result['trade_status'] ?? '') === 'TRADE_SUCCESS' || ($result['trade_status'] ?? '') === 'TRADE_FINISHED') {
            $this->processPaymentSuccess($result['out_trade_no'], $result['transaction_id'], $result['amount'], 2);
        }

        return 'success';
    }

    /**
     * 处理支付成功
     */
    private function processPaymentSuccess($outTradeNo, $transactionId, $amount, $payType)
    {
        DB::beginTransaction();
        try {
            // 判断是订单支付还是充值支付
            $order = Order::where('order_no', $outTradeNo)->first();
            if ($order) {
                // 重复支付防护
                if ($order->status != 0) {
                    Log::info('订单已支付，跳过', ['order_no' => $outTradeNo, 'status' => $order->status]);
                    DB::commit();
                    return;
                }

                $order->update([
                    'status' => 1,
                    'pay_type' => $payType,
                    'pay_no' => $transactionId,
                    'pay_time' => Carbon::now(),
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'action' => 2,
                    'action_name' => '支付成功',
                    'operator_type' => 'system',
                    'operator_id' => 0,
                    'remark' => '第三方支付成功，金额 ¥' . $amount,
                ]);

                // 更新payments表
                DB::table('payments')->where('order_no', $outTradeNo)->update([
                    'third_payment_no' => $transactionId,
                    'status' => 1,
                    'pay_time' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // 分销佣金自动计算
                $this->calculateDistributeCommission($order);
            } else {
                // 充值支付
                $recharge = DB::table('user_recharges')->where('pay_no', $outTradeNo)->first();
                if ($recharge && $recharge->status == 0) {
                    DB::table('user_recharges')->where('id', $recharge->id)->update([
                        'status' => 1,
                        'paid_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    // 更新用户余额
                    $user = DB::table('users')->where('id', $recharge->user_id)->first();
                    $beforeBalance = $user->balance ?? 0;
                    $totalAmount = $recharge->amount + $recharge->give_amount;
                    DB::table('users')->where('id', $recharge->user_id)->increment('balance', $totalAmount);

                    // 记录账户日志
                    DB::table('user_account_logs')->insert([
                        'user_id' => $recharge->user_id,
                        'type' => 1,
                        'amount' => $totalAmount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $beforeBalance + $totalAmount,
                        'remark' => '充值到账',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }

            DB::commit();
            Log::info('支付成功处理完成', ['out_trade_no' => $outTradeNo, 'amount' => $amount]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('支付成功处理失败', ['error' => $e->getMessage(), 'out_trade_no' => $outTradeNo]);
            throw $e;
        }
    }

    /**
     * 计算分销佣金
     */
    private function calculateDistributeCommission($order)
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
                if ($distributeGoods->commission_type == 1) {
                    $commission = round($item->pay_amount * $distributeGoods->commission_rate / 100, 2);
                } else {
                    $commission = $distributeGoods->commission_amount * $item->quantity;
                }
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
     * 微信退款回调
     */
    public function wechatRefund(Request $request)
    {
        Log::info('微信退款回调', $request->all());
        return response('SUCCESS', 200);
    }

    /**
     * 支付宝退款回调
     */
    public function alipayRefund(Request $request)
    {
        Log::info('支付宝退款回调', $request->all());
        return 'success';
    }
}
