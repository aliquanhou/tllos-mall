<?php
namespace App\Modules\Order\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductSku;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
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

    /**
     * P1-REFUND-FOUNDATION: Refund audit via unified RefundService.
     *
     * CRITICAL: Approval moves refund to PROCESSING, NOT SUCCESS.
     * No fake third-party refund number. No direct order status change.
     * Actual third-party refund API call and final SUCCESS state will be
     * handled in P1-REFUND-PROVIDER-INTEGRATION.
     */
    public function refundAudit($id, Request $request)
    {
        $request->validate(['action' => 'required|string|in:approve,reject', 'reason' => 'nullable|string']);

        $refundService = app(RefundService::class);
        $adminId = $request->user()->id ?? 0;

        if ($request->action == 'approve') {
            $result = $refundService->approveRefund($id, $adminId);
            if (!$result['success']) {
                return $this->error($result['message']);
            }

            // Log audit action (order status NOT changed here — waits for provider confirmation)
            $refund = DB::table('order_refunds')->where('id', $id)->first();
            if ($refund) {
                OrderLog::create([
                    'order_id' => $refund->order_id,
                    'order_no' => DB::table('orders')->where('id', $refund->order_id)->value('order_no') ?? '',
                    'action' => 6,
                    'action_name' => '退款审核通过',
                    'operator_type' => 'admin',
                    'operator_id' => $adminId,
                    'remark' => "退款审核通过 ¥{$refund->refund_amount}，进入处理中状态（等待第三方退款确认）",
                ]);
            }

            return $this->success(['status' => RefundStatus::PROCESSING], '退款审核通过，进入处理中状态');
        } else {
            $result = $refundService->rejectRefund($id, $request->reason ?? '', $adminId);
            if (!$result['success']) {
                return $this->error($result['message']);
            }
            return $this->success(null, '已拒绝退款');
        }
    }
}
