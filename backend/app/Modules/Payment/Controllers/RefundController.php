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

class RefundController extends BaseController
{
    /**
     * 退款列表
     */
    public function index(Request $request)
    {
        $query = DB::table('refunds')->orderBy('id', 'desc');

        if ($request->order_no) {
            $query->where('refund_no', 'like', '%' . $request->order_no . '%');
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $total = $query->count();
        $list = $query->offset(($request->page ?? 1) - 1)
            ->limit($request->limit ?? 20)
            ->get();

        return $this->success([
            'list' => $list,
            'total' => $total,
            'stats' => [
                'total' => DB::table('refunds')->count(),
                'pending' => DB::table('refunds')->where('status', 0)->count(),
                'approved' => DB::table('refunds')->where('status', 1)->count(),
                'rejected' => DB::table('refunds')->where('status', 2)->count(),
                'total_amount' => DB::table('refunds')->where('status', 1)->sum('refund_amount'),
            ],
        ]);
    }

    /**
     * 申请退款
     */
    public function refund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $order = Order::find($request->order_id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if (!in_array($order->status, [1, 2, 3])) {
            return $this->error('当前订单状态不支持退款，状态: ' . $order->status);
        }
        if ($request->amount > $order->pay_amount) {
            return $this->error('退款金额不能超过订单支付金额');
        }

        $refundNo = 'REF' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // 调用第三方退款
        $refundResult = $this->callThirdPartyRefund($order, $request->amount, $refundNo, $request->reason ?? '');

        DB::beginTransaction();
        try {
            // 创建退款记录
            DB::table('refunds')->insert([
                'refund_no' => $refundNo,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'merchant_id' => $order->merchant_id,
                'refund_amount' => $request->amount,
                'reason' => $request->reason ?? '',
                'status' => $refundResult['success'] ? 1 : 0,
                'audit_at' => $refundResult['success'] ? Carbon::now() : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            if ($refundResult['success']) {
                // 更新订单状态为退款中
                $order->update(['status' => 5]);

                // 订单日志
                OrderLog::create([
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'action' => 6,
                    'action_name' => '退款成功',
                    'operator_type' => 'admin',
                    'operator_id' => $request->user()->id ?? 0,
                    'remark' => '退款金额 ¥' . $request->amount . '，原因: ' . ($request->reason ?? ''),
                ]);

                // 库存回滚
                $orderItems = DB::table('order_items')->where('order_id', $order->id)->get();
                foreach ($orderItems as $item) {
                    DB::table('products')->where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                // 回滚分销佣金
                if ($order->commission > 0) {
                    DB::table('distribute_orders')->where('order_id', $order->id)->update(['status' => 2]);
                    if ($order->agent_id > 0) {
                        DB::table('distribute_agents')->where('id', $order->agent_id)->decrement('total_income', $order->commission);
                    }
                }
            }

            DB::commit();
            return $this->success([
                'refund_no' => $refundNo,
                'status' => $refundResult['success'] ? 1 : 0,
            ], $refundResult['success'] ? '退款成功' : '退款申请已提交，等待处理');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('退款处理失败', ['error' => $e->getMessage()]);
            return $this->error('退款处理失败: ' . $e->getMessage());
        }
    }

    /**
     * 调用第三方退款
     */
    private function callThirdPartyRefund($order, $amount, $outRefundNo, $reason)
    {
        $params = [
            'out_trade_no' => $order->order_no,
            'out_refund_no' => $outRefundNo,
            'amount' => $amount,
            'total_amount' => $order->pay_amount,
            'reason' => $reason,
        ];

        if ($order->pay_type == 1) {
            $service = new WechatPayService();
            return $service->refund($params);
        } elseif ($order->pay_type == 2) {
            $service = new AlipayService();
            return $service->refund($params);
        } else {
            return ['success' => true, 'refund_id' => 'BALANCE' . time()];
        }
    }
}
