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
