<?php
namespace App\Modules\AfterSale\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AfterSaleController extends BaseController
{
    // 售后类型
    const TYPE_REFUND_RETURN = 1; // 退货退款
    const TYPE_REFUND_ONLY = 2;   // 仅退款
    const TYPE_EXCHANGE = 3;      // 换货
    const TYPE_REISSUE = 4;       // 补发

    // 售后状态
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_APPROVED = 1;     // 审核通过
    const STATUS_REJECTED = 2;     // 审核拒绝
    const STATUS_COMPLETED = 3;    // 已完成
    const STATUS_WAIT_RETURN = 4;  // 待退货
    const STATUS_WAIT_RECEIVE = 6; // 待收货
    const STATUS_CANCELLED = 5;    // 已取消

    public function index(Request $request) {
        $query = DB::table('order_after_sales');
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('order_no', 'like', '%'.$request->keyword.'%')
                  ->orWhere('reason', 'like', '%'.$request->keyword.'%');
            });
        }
        if ($request->filled('type') && $request->type !== '') $query->where('type', $request->type);
        if ($request->filled('status') && $request->status !== '') $query->where('status', $request->status);
        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = [
            'total' => DB::table('order_after_sales')->count(),
            'pending' => DB::table('order_after_sales')->where('status', self::STATUS_PENDING)->count(),
            'wait_return' => DB::table('order_after_sales')->where('status', self::STATUS_WAIT_RETURN)->count(),
            'wait_receive' => DB::table('order_after_sales')->where('status', self::STATUS_WAIT_RECEIVE)->count(),
            'completed' => DB::table('order_after_sales')->where('status', self::STATUS_COMPLETED)->count(),
            'rejected' => DB::table('order_after_sales')->where('status', self::STATUS_REJECTED)->count(),
        ];
        return $this->success(['list'=>$list, 'total'=>$total, 'page'=>$page, 'limit'=>$limit, 'stats'=>$stats]);
    }

    public function show($id) {
        $item = DB::table('order_after_sales')->where('id', $id)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        $logs = DB::table('after_sale_logs')->where('after_sale_id', $id)->orderBy('id', 'asc')->get();
        $order = DB::table('orders')->where('id', $item->order_id)->first();
        $orderItems = DB::table('order_items')->where('order_id', $item->order_id)->get();
        return $this->success(['info'=>$item, 'logs'=>$logs, 'order'=>$order, 'order_items'=>$orderItems]);
    }

    public function audit(Request $request, $id) {
        $v = $request->validate([
            'status' => 'required|in:1,2',
            'audit_remark' => 'nullable|string',
        ]);
        $item = DB::table('order_after_sales')->where('id', $id)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        if ($item->status != self::STATUS_PENDING) return $this->error('当前状态不能审核');

        if ($v['status'] == self::STATUS_APPROVED) {
            // 仅退款(type=2)直接完成，退货退款(type=1)进入待退货状态
            $newStatus = ($item->type == self::TYPE_REFUND_ONLY) ? self::STATUS_COMPLETED : self::STATUS_WAIT_RETURN;
            $updateData = [
                'status' => $newStatus,
                'audit_remark' => $v['audit_remark'] ?? null,
                'audit_at' => now(),
                'updated_at' => now(),
            ];
            if ($newStatus == self::STATUS_COMPLETED) {
                $updateData['refund_time'] = now();
                $updateData['completed_at'] = now();
            }
            DB::table('order_after_sales')->where('id', $id)->update($updateData);
            DB::table('after_sale_logs')->insert([
                'after_sale_id' => $id,
                'action' => '审核通过',
                'admin_id' => 1,
                'remark' => $v['audit_remark'] ?? '',
                'created_at' => now(),
            ]);
            if ($newStatus == self::STATUS_WAIT_RETURN) {
                DB::table('after_sale_logs')->insert([
                    'after_sale_id' => $id,
                    'action' => '等待用户退货',
                    'admin_id' => 1,
                    'remark' => '请用户寄回商品',
                    'created_at' => now(),
                ]);
            }
            return $this->success(null, $newStatus == self::STATUS_COMPLETED ? '审核通过，已完成退款' : '审核通过，等待用户退货');
        } else {
            DB::table('order_after_sales')->where('id', $id)->update([
                'status' => self::STATUS_REJECTED,
                'audit_remark' => $v['audit_remark'] ?? null,
                'audit_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('after_sale_logs')->insert([
                'after_sale_id' => $id,
                'action' => '审核拒绝',
                'admin_id' => 1,
                'remark' => $v['audit_remark'] ?? '',
                'created_at' => now(),
            ]);
            return $this->success(null, '已拒绝');
        }
    }

    public function receive($id) {
        $item = DB::table('order_after_sales')->where('id', $id)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        if ($item->status != self::STATUS_WAIT_RECEIVE) return $this->error('当前状态不能确认收货');

        DB::table('order_after_sales')->where('id', $id)->update([
            'status' => self::STATUS_COMPLETED,
            'receive_time' => now(),
            'refund_time' => now(),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('after_sale_logs')->insert([
            'after_sale_id' => $id,
            'action' => '确认收货',
            'admin_id' => 1,
            'remark' => '商家已收到退货，完成退款',
            'created_at' => now(),
        ]);
        // 更新订单状态为已退款
        if ($item->order_id) {
            DB::table('orders')->where('id', $item->order_id)->update(['status' => 6]);
        }
        return $this->success(null, '已确认收货，退款完成');
    }

    public function complete($id) {
        $item = DB::table('order_after_sales')->where('id', $id)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        if (!in_array($item->status, [self::STATUS_APPROVED, self::STATUS_WAIT_RETURN, self::STATUS_WAIT_RECEIVE])) {
            return $this->error('当前状态不能完成');
        }
        DB::table('order_after_sales')->where('id', $id)->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'refund_time' => now(),
            'updated_at' => now(),
        ]);
        DB::table('after_sale_logs')->insert([
            'after_sale_id' => $id,
            'action' => '售后完成',
            'admin_id' => 1,
            'created_at' => now(),
        ]);
        return $this->success(null, '已完成');
    }
}
