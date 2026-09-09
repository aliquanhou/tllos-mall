<?php
namespace App\Modules\Order\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AdminOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = Order::with(['items:id,order_id,product_name,product_image,sku_text,price,quantity,pay_amount', 'user:id,nickname,mobile']);
        if ($request->order_no) $query->where('order_no', 'like', "%{$request->order_no}%");
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        if ($request->user_mobile) $query->whereHas('user', fn($q) => $q->where('mobile', 'like', "%{$request->user_mobile}%"));
        if ($request->start_time) $query->where('created_at', '>=', $request->start_time);
        if ($request->end_time) $query->where('created_at', '<=', $request->end_time);

        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?: 20);
        $stats = [
            'total' => Order::count(),
            'wait_pay' => Order::where('status', 0)->count(),
            'wait_ship' => Order::where('status', 1)->count(),
            'wait_confirm' => Order::where('status', 2)->count(),
            'completed' => Order::where('status', 3)->count(),
            'refund' => Order::whereIn('status', [5, 6])->count(),
            'total_amount' => Order::where('status', '>=', 1)->sum('pay_amount'),
        ];
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'stats' => $stats]);
    }

    public function show($id)
    {
        $order = Order::with(['items', 'logs' => fn($q) => $q->orderBy('id', 'asc'), 'user:id,nickname,mobile,avatar'])->find($id);
        if (!$order) return $this->error('订单不存在', 404);
        return $this->success($order);
    }

    public function ship($id, Request $request)
    {
        $request->validate(['express_company' => 'required|string', 'express_no' => 'required|string']);
        $order = Order::find($id);
        if (!$order) return $this->error('订单不存在', 404);
        if ($order->status != 1) return $this->error('当前状态不能发货');

        $order->update([
            'status' => 2,
            'express_company' => $request->express_company,
            'express_no' => $request->express_no,
            'ship_time' => Carbon::now(),
            'auto_confirm_at' => Carbon::now()->addDays(7),
        ]);

        OrderLog::create([
            'order_id' => $order->id, 'order_no' => $order->order_no,
            'action' => 3, 'action_name' => '发货',
            'operator_type' => 'admin', 'operator_id' => $request->user()->id ?? 0,
            'remark' => "{$request->express_company} 单号：{$request->express_no}",
        ]);

        return $this->success(null, '发货成功');
    }

    public function remark($id, Request $request)
    {
        $order = Order::find($id);
        if (!$order) return $this->error('订单不存在', 404);
        $order->update(['admin_remark' => $request->remark ?? '']);
        return $this->success(null, '备注已更新');
    }

    public function refundList(Request $request)
    {
        $query = DB::table('order_refunds');
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        if ($request->refund_no) $query->where('refund_no', 'like', "%{$request->refund_no}%");
        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?: 20);
        return $this->success(['list' => $list->items(), 'total' => $list->total()]);
    }

    public function refundAudit($id, Request $request)
    {
        $request->validate(['action' => 'required|string|in:approve,reject', 'reason' => 'nullable|string']);
        $refund = DB::table('order_refunds')->where('id', $id)->first();
        if (!$refund) return $this->error('退款单不存在', 404);
        if ($refund->status != 0) return $this->error('当前状态不能审核');

        DB::beginTransaction();
        try {
            if ($request->action == 'approve') {
                DB::table('order_refunds')->where('id', $id)->update([
                    'status' => 5, 'refund_time' => Carbon::now(),
                    'refund_no_third' => 'REF' . time(), 'updated_at' => Carbon::now(),
                ]);
                $order = Order::find($refund->order_id);
                if ($order) {
                    $order->update(['status' => 6]);
                    if ($refund->order_item_id) {
                        $item = OrderItem::find($refund->order_item_id);
                        if ($item) {
                            if ($item->sku_id) ProductSku::where('id', $item->sku_id)->increment('stock', $item->quantity);
                            else Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                            $item->update(['is_refunded' => 1]);
                        }
                    }
                    OrderLog::create([
                        'order_id' => $order->id, 'order_no' => $order->order_no,
                        'action' => 6, 'action_name' => '退款成功',
                        'operator_type' => 'admin', 'operator_id' => 0,
                        'remark' => "退款 ¥{$refund->refund_amount}",
                    ]);
                }
            } else {
                DB::table('order_refunds')->where('id', $id)->update([
                    'status' => 2, 'refuse_reason' => $request->reason ?? '不符合退款条件',
                    'updated_at' => Carbon::now(),
                ]);
            }
            DB::commit();
            return $this->success(null, $request->action == 'approve' ? '退款已通过' : '已拒绝退款');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('操作失败: ' . $e->getMessage());
        }
    }
}
