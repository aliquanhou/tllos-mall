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
     * 支付宝异步回调（notify_url，POST）
     */
    public function alipay(Request $request)
    {
        Log::info('支付宝异步回调', $request->all());

        $service = new AlipayService();
        $result = $service->verifyNotify($request->all());

        if (!$result['success']) {
            Log::error('支付宝回调验签失败', $result);
            return 'fail';
        }

        $tradeStatus = $result['trade_status'] ?? '';
        if ($service->isSandbox() || $tradeStatus === 'TRADE_SUCCESS' || $tradeStatus === 'TRADE_FINISHED') {
            $this->processPaymentSuccess($result['out_trade_no'], $result['transaction_id'], $result['amount'], 2);
        }

        return 'success';
    }

    /**
     * 支付宝同步返回（return_url，GET）
     * 支付完成后支付宝跳转回此地址，验签后重定向到前端支付结果页
     */
    public function alipayReturn(Request $request)
    {
        Log::info('支付宝同步返回', $request->all());

        $params = $request->all();
        $outTradeNo = $params['out_trade_no'] ?? '';

        if (empty($outTradeNo)) {
            return redirect(config('app.url') . '/orders?pay_result=error');
        }

        $service = new AlipayService();

        // 沙箱模式直接跳转成功页
        if ($service->isSandbox()) {
            return redirect(config('app.url') . '/pay/result/' . $outTradeNo . '?status=success');
        }

        // 验签
        $verifyResult = $service->verifyReturn($params);
        if (!$verifyResult['success']) {
            Log::warning('支付宝同步返回验签失败', $params);
            return redirect(config('app.url') . '/pay/result/' . $outTradeNo . '?status=pending');
        }

        $tradeStatus = $params['trade_status'] ?? '';
        if ($tradeStatus === 'TRADE_SUCCESS' || $tradeStatus === 'TRADE_FINISHED') {
            // 同步返回时也尝试处理支付成功（异步通知可能还没到）
            $this->processPaymentSuccess(
                $outTradeNo,
                $params['trade_no'] ?? '',
                $params['total_amount'] ?? 0,
                2
            );
            return redirect(config('app.url') . '/pay/result/' . $outTradeNo . '?status=success');
        }

        // 其他状态（如WAIT_BUYER_PAY）跳转到待确认页
        return redirect(config('app.url') . '/pay/result/' . $outTradeNo . '?status=pending');
    }

    /**
     * 处理支付成功
     *
     * P1-B: 回调金额必须与订单金额、支付记录金额一致
     * P1-C: 精确绑定当前支付记录，不批量更新同订单其他支付
     *
     * Fail-Closed 顺序：
     * 1. 参数完整性检查
     * 2. 查询本地订单
     * 3. 查询对应支付记录（精确绑定）
     * 4. 金额一致性校验（callback == order == payment）
     * 5. 支付记录状态校验
     * 6. 第三方交易号唯一性检查
     * 7. DB事务内更新 Payment → Order
     */
    private function processPaymentSuccess($outTradeNo, $transactionId, $amount, $payType)
    {
        // P1-B Step 1: 参数完整性
        if (empty($outTradeNo)) {
            Log::error('支付回调失败：out_trade_no为空', ['transaction_id' => $transactionId, 'amount' => $amount]);
            return;
        }
        if (empty($transactionId)) {
            Log::error('支付回调失败：transaction_id为空', ['out_trade_no' => $outTradeNo, 'amount' => $amount]);
            return;
        }
        if ($amount === null || $amount === '' || $amount < 0) {
            Log::error('支付回调失败：金额无效', ['out_trade_no' => $outTradeNo, 'transaction_id' => $transactionId, 'amount' => $amount]);
            return;
        }

        // P1-PRECONDITION Step 2: Provider-Originated Exact Identity
        // out_trade_no is now payment_no (new protocol). Fall back to order_no (legacy).
        $payment = DB::table('payments')
            ->where('payment_no', $outTradeNo)
            ->first();

        if (!$payment) {
            // Legacy compatibility: old payments used order_no as out_trade_no
            $payment = DB::table('payments')
                ->where('order_no', $outTradeNo)
                ->where('status', 0)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        }

        if (!$payment) {
            // May be a recharge payment
            $this->processRechargeSuccess($outTradeNo, $transactionId, $amount);
            return;
        }

        // Query order via payment.order_no (exact binding)
        $order = Order::where('order_no', $payment->order_no)->first();
        if (!$order) {
            Log::error('支付回调失败：支付记录对应订单不存在', [
                'payment_no' => $payment->payment_no,
                'order_no' => $payment->order_no,
                'transaction_id' => $transactionId,
            ]);
            return;
        }

        // Idempotency: order already paid → skip
        if ($order->status != 0) {
            Log::info('订单已支付，回调跳过', ['order_no' => $order->order_no, 'status' => $order->status, 'transaction_id' => $transactionId]);
            return;
        }

        // P1-C Step 3: payment record status check
        if ($payment->status != 0) {
            Log::info('支付记录已处理，回调跳过', [
                'payment_no' => $payment->payment_no,
                'status' => $payment->status,
            ]);
            return;
        }

        // P1-B Step 4: 金额一致性校验（使用 bccomp 精确比较，避免浮点误差）
        $callbackAmount = (string)$amount;
        $orderAmount = (string)$order->pay_amount;
        $paymentAmount = (string)$payment->amount;

        $amountMatchOrder = bccomp($callbackAmount, $orderAmount, 2) === 0;
        $amountMatchPayment = bccomp($callbackAmount, $paymentAmount, 2) === 0;

        if (!$amountMatchOrder || !$amountMatchPayment) {
            Log::critical('支付回调金额不一致，拒绝支付成功', [
                'order_no' => $outTradeNo,
                'payment_no' => $payment->payment_no,
                'callback_amount' => $callbackAmount,
                'order_amount' => $orderAmount,
                'payment_amount' => $paymentAmount,
                'third_payment_no' => $transactionId,
                'pay_type' => $payType,
            ]);
            return;
        }

        // P1-C Step 6: 第三方交易号唯一性检查
        $existingPayment = DB::table('payments')
            ->where('third_payment_no', $transactionId)
            ->where('status', 1)
            ->where('id', '!=', $payment->id)
            ->first();

        if ($existingPayment) {
            Log::critical('支付回调失败：第三方交易号已被其他支付记录使用', [
                'incoming_order_no' => $outTradeNo,
                'incoming_payment_no' => $payment->payment_no,
                'existing_order_no' => $existingPayment->order_no,
                'existing_payment_no' => $existingPayment->payment_no,
                'third_payment_no' => $transactionId,
            ]);
            return;
        }

        // Step 7: DB事务内更新 Payment → Order
        DB::beginTransaction();
        try {
            // 重新读取并锁定支付记录（防止并发回调）
            $lockedPayment = DB::table('payments')
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment || $lockedPayment->status != 0) {
                Log::info('支付记录在事务中已被处理，跳过', ['payment_no' => $payment->payment_no]);
                DB::commit();
                return;
            }

            // 重新锁定订单
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            if (!$lockedOrder || $lockedOrder->status != 0) {
                Log::info('订单在事务中已被处理，跳过', ['order_no' => $outTradeNo]);
                DB::commit();
                return;
            }

            // P1-C: 只更新当前精确绑定的支付记录
            DB::table('payments')->where('id', $lockedPayment->id)->update([
                'third_payment_no' => $transactionId,
                'status' => 1,
                'pay_time' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // 更新订单
            $lockedOrder->update([
                'status' => 1,
                'pay_type' => $payType,
                'pay_no' => $transactionId,
                'pay_time' => Carbon::now(),
            ]);

            // 订单日志
            OrderLog::create([
                'order_id' => $lockedOrder->id,
                'order_no' => $lockedOrder->order_no,
                'action' => 2,
                'action_name' => '支付成功',
                'operator_type' => 'system',
                'operator_id' => 0,
                'remark' => '第三方支付成功，金额 ¥' . $callbackAmount . '，交易号 ' . $transactionId,
            ]);

            // 分销佣金
            $this->calculateDistributeCommission($lockedOrder);

            DB::commit();
            Log::info('支付成功处理完成', [
                'order_no' => $outTradeNo,
                'payment_no' => $lockedPayment->payment_no,
                'amount' => $callbackAmount,
                'transaction_id' => $transactionId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('支付成功处理失败', [
                'error' => $e->getMessage(),
                'out_trade_no' => $outTradeNo,
                'transaction_id' => $transactionId,
            ]);
            throw $e;
        }
    }

    /**
     * 处理充值支付成功
     */
    private function processRechargeSuccess($outTradeNo, $transactionId, $amount)
    {
        $recharge = DB::table('user_recharges')->where('pay_no', $outTradeNo)->first();
        if (!$recharge) {
            Log::error('支付回调失败：订单和充值记录都不存在', [
                'out_trade_no' => $outTradeNo,
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);
            return;
        }

        // 充值金额校验
        if (bccomp((string)$amount, (string)$recharge->amount, 2) !== 0) {
            Log::critical('充值回调金额不一致', [
                'pay_no' => $outTradeNo,
                'callback_amount' => $amount,
                'recharge_amount' => $recharge->amount,
            ]);
            return;
        }

        if ($recharge->status != 0) {
            Log::info('充值已处理，跳过', ['pay_no' => $outTradeNo]);
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('user_recharges')->where('id', $recharge->id)->update([
                'status' => 1,
                'paid_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $user = DB::table('users')->where('id', $recharge->user_id)->first();
            $beforeBalance = $user->balance ?? 0;
            $totalAmount = $recharge->amount + $recharge->give_amount;
            DB::table('users')->where('id', $recharge->user_id)->increment('balance', $totalAmount);

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

            DB::commit();
            Log::info('充值成功处理完成', ['pay_no' => $outTradeNo, 'amount' => $totalAmount]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('充值处理失败', ['error' => $e->getMessage(), 'pay_no' => $outTradeNo]);
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
