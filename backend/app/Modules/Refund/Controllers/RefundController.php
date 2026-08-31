<?php
namespace App\Modules\Refund\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RefundController extends BaseController
{
    public function index(Request $request)
    {
        $refunds = DB::table('order_refunds')->where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')->paginate($request->limit ?: 10);
        return $this->success(['list' => $refunds->items(), 'total' => $refunds->total()]);
    }

    public function show($id, Request $request)
    {
        $refund = DB::table('order_refunds')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$refund) return $this->error('退款单不存在', 404);
        return $this->success($refund);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'order_item_id' => 'nullable|integer',
            'type' => 'required|integer|in:1,2',
            'reason' => 'required|string',
            'refund_amount' => 'required|numeric|min:0.01',
        ]);

        $userId = $request->user()->id;
        $order = Order::where('id', $request->order_id)->where('user_id', $userId)->first();
        if (!$order) return $this->error('订单不存在', 404);
        if (!in_array($order->status, [1, 2, 3])) return $this->error('当前状态不能申请退款');

        $refundNo = 'RF' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            DB::table('order_refunds')->insert([
                'refund_no' => $refundNo,
                'order_id' => $order->id,
                'order_item_id' => $request->order_item_id ?? 0,
                'user_id' => $userId,
                'merchant_id' => $order->merchant_id,
                'type' => $request->type,
                'refund_amount' => $request->refund_amount,
                'reason' => $request->reason,
                'description' => $request->description ?? '',
                'images' => $request->images ? json_encode($request->images) : null,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            OrderLog::create([
                'order_id' => $order->id, 'order_no' => $order->order_no,
                'action' => 6, 'action_name' => '申请退款',
                'operator_type' => 'user', 'operator_id' => $userId,
                'remark' => "申请退款 ¥{$request->refund_amount}，原因：{$request->reason}",
            ]);

            DB::commit();
            return $this->success(['refund_no' => $refundNo], '退款申请已提交');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('申请失败: ' . $e->getMessage());
        }
    }

    public function cancel($id, Request $request)
    {
        $refund = DB::table('order_refunds')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$refund) return $this->error('退款单不存在', 404);
        if ($refund->status != 0) return $this->error('当前状态不能取消');

        DB::table('order_refunds')->where('id', $id)->update(['status' => 6, 'updated_at' => Carbon::now()]);
        return $this->success(null, '已取消退款申请');
    }
}
