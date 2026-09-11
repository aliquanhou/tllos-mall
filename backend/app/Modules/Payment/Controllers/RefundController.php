<?php
namespace App\Modules\Payment\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderLog;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * P1-REFUND-PROVIDER-PRECONDITION: Canonical Refund Entry
 *
 * This controller now uses RefundService as the canonical refund entry.
 * It NO LONGER writes to the legacy `refunds` table.
 * It NO LONGER calls third-party refund APIs directly.
 *
 * Flow:
 *   createRefund (REQUESTED) → approveRefund (PROCESSING)
 *
 * Provider Integration (actual Alipay/WeChat refund API calls) will be
 * handled in the next phase: P1-REFUND-PROVIDER-INTEGRATION-IMPLEMENTATION.
 *
 * The legacy `refunds` table is now READ-ONLY / LEGACY-ONLY.
 */
class RefundController extends BaseController
{
    /**
     * Refund list — reads from canonical order_refunds table.
     * Legacy refunds table is no longer written to.
     */
    public function index(Request $request)
    {
        $query = DB::table('order_refunds')->orderBy('id', 'desc');

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
                'total' => DB::table('order_refunds')->count(),
                'pending' => DB::table('order_refunds')->where('status', RefundStatus::REQUESTED)->count(),
                'processing' => DB::table('order_refunds')->where('status', RefundStatus::PROCESSING)->count(),
                'success' => DB::table('order_refunds')->where('status', RefundStatus::SUCCESS)->count(),
                'total_amount' => DB::table('order_refunds')->where('status', RefundStatus::SUCCESS)->sum('refund_amount'),
            ],
        ]);
    }

    /**
     * Admin direct refund — canonical entry via RefundService.
     *
     * Creates a REQUESTED refund and immediately approves it to PROCESSING.
     * Does NOT call third-party APIs (next phase).
     * Does NOT modify order status or rollback stock (next phase).
     * Does NOT write to legacy refunds table.
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

        $refundService = app(RefundService::class);
        $adminId = $request->user()->id ?? 0;

        // Step 1: Create refund request (REQUESTED) with cumulative amount check
        $result = $refundService->createRefund([
            'order_id' => $order->id,
            'order_item_id' => 0,
            'user_id' => $order->user_id,
            'type' => 1,
            'refund_amount' => $request->amount,
            'reason' => $request->reason ?? '后台直接退款',
            'description' => '管理员后台直接退款',
        ]);

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        // Step 2: Immediately approve to PROCESSING
        // Provider Integration phase will handle actual third-party refund call
        $approveResult = $refundService->approveRefund($result['refund_id'], $adminId);
        if (!$approveResult['success']) {
            return $this->error($approveResult['message']);
        }

        // Log the action (order status NOT changed here — waits for provider confirmation)
        OrderLog::create([
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'action' => 6,
            'action_name' => '后台退款申请',
            'operator_type' => 'admin',
            'operator_id' => $adminId,
            'remark' => "后台直接退款 ¥{$request->amount}，原因：" . ($request->reason ?? '后台直接退款') . "，已进入处理中状态，等待第三方退款确认",
        ]);

        return $this->success([
            'refund_no' => $result['refund_no'],
            'refund_id' => $result['refund_id'],
            'status' => RefundStatus::PROCESSING,
            'message' => '退款申请已提交，进入处理中状态。第三方退款将在后续阶段处理。',
        ], '退款申请已提交');
    }
}
