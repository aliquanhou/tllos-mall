<?php
namespace App\Modules\Refund\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
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

    /**
     * P1-REFUND-FOUNDATION: Create refund via unified RefundService.
     * Service handles cumulative amount check, payment identity, atomic creation.
     */
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

        $refundService = app(RefundService::class);
        $result = $refundService->createRefund([
            'order_id' => $order->id,
            'order_item_id' => $request->order_item_id ?? 0,
            'user_id' => $userId,
            'type' => $request->type,
            'refund_amount' => $request->refund_amount,
            'reason' => $request->reason,
            'description' => $request->description ?? '',
            'images' => $request->images ?? null,
        ]);

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        OrderLog::create([
            'order_id' => $order->id, 'order_no' => $order->order_no,
            'action' => 6, 'action_name' => '申请退款',
            'operator_type' => 'user', 'operator_id' => $userId,
            'remark' => "申请退款 ¥{$request->refund_amount}，原因：{$request->reason}",
        ]);

        return $this->success([
            'refund_no' => $result['refund_no'],
            'refund_id' => $result['refund_id'],
            'status' => RefundStatus::REQUESTED,
        ], '退款申请已提交');
    }

    public function cancel($id, Request $request)
    {
        $refundService = app(RefundService::class);
        $result = $refundService->cancelRefund($id, $request->user()->id);

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success(null, '已取消退款申请');
    }
}
