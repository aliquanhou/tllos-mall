<?php
namespace App\Modules\AfterSale\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAfterSaleController extends BaseController
{
    const STATUS_PENDING = 0;
    const STATUS_WAIT_RETURN = 4;
    const STATUS_WAIT_RECEIVE = 6;
    const STATUS_CANCELLED = 5;

    // 售后原因枚举
    const REASONS = [
        '质量问题', '发错货', '不想要了', '尺寸不合适',
        '描述不符', '物流问题', '商品损坏', '其他原因'
    ];

    public function lists(Request $request) {
        $userId = $request->user()->id;
        $query = DB::table('order_after_sales')->where('user_id', $userId);
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        $list = $query->orderBy('id', 'desc')->paginate($request->get('limit', 20));
        return $this->success(['list'=>$list->items(), 'total'=>$list->total()]);
    }

    public function reasons() {
        return $this->success(['reasons' => self::REASONS]);
    }

    public function add(Request $request) {
        $userId = $request->user()->id;
        $v = $request->validate([
            'order_id' => 'required|integer',
            'order_item_id' => 'nullable|integer',
            'type' => 'required|integer|in:1,2,3,4',
            'reason' => 'required|string',
            'description' => 'nullable|string',
            'images' => 'nullable|string',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $order = DB::table('orders')->where('id', $v['order_id'])->where('user_id', $userId)->first();
        if (!$order) return $this->error('订单不存在');
        if (!in_array($order->status, [2, 3])) return $this->error('当前订单状态不能申请售后');

        $orderItem = null;
        if (!empty($v['order_item_id'])) {
            $orderItem = DB::table('order_items')->where('id', $v['order_item_id'])->where('order_id', $v['order_id'])->first();
        }

        $data = [
            'order_id' => $v['order_id'],
            'order_no' => $order->order_no,
            'order_item_id' => $v['order_item_id'] ?? 0,
            'user_id' => $userId,
            'merchant_id' => $order->merchant_id,
            'type' => $v['type'],
            'reason' => $v['reason'],
            'description' => $v['description'] ?? '',
            'images' => $v['images'] ?? null,
            'refund_amount' => $v['refund_amount'] ?? ($orderItem ? $orderItem->pay_amount : $order->pay_amount),
            'status' => self::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $id = DB::table('order_after_sales')->insertGetId($data);

        DB::table('order_logs')->insert([
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'action' => 5,
            'action_name' => '申请售后',
            'operator_type' => 'user',
            'operator_id' => $userId,
            'remark' => $v['reason'],
            'created_at' => now(),
        ]);

        return $this->success(['id'=>$id], '申请成功');
    }

    public function detail(Request $request, $id) {
        $afterSale = DB::table('order_after_sales')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$afterSale) return $this->error('售后单不存在', 404);
        $logs = DB::table('after_sale_logs')->where('after_sale_id', $id)->orderBy('id', 'asc')->get();
        return $this->success(['info'=>$afterSale, 'logs'=>$logs]);
    }

    public function returnShip(Request $request, $id) {
        $userId = $request->user()->id;
        $v = $request->validate([
            'return_express_company' => 'required|string',
            'return_express_no' => 'required|string',
        ]);
        $item = DB::table('order_after_sales')->where('id', $id)->where('user_id', $userId)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        if ($item->status != self::STATUS_WAIT_RETURN) return $this->error('当前状态不能填写退货物流');

        DB::table('order_after_sales')->where('id', $id)->update([
            'status' => self::STATUS_WAIT_RECEIVE,
            'return_express_company' => $v['return_express_company'],
            'return_express_no' => $v['return_express_no'],
            'return_ship_time' => now(),
            'updated_at' => now(),
        ]);
        DB::table('after_sale_logs')->insert([
            'after_sale_id' => $id,
            'action' => '用户退货发货',
            'admin_id' => 0,
            'remark' => $v['return_express_company'] . ' 单号：' . $v['return_express_no'],
            'created_at' => now(),
        ]);
        return $this->success(null, '退货物流已提交，等待商家收货');
    }

    public function cancel(Request $request, $id) {
        $item = DB::table('order_after_sales')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$item) return $this->error('售后单不存在', 404);
        if (!in_array($item->status, [self::STATUS_PENDING, self::STATUS_WAIT_RETURN])) {
            return $this->error('当前状态不能取消');
        }
        DB::table('order_after_sales')->where('id', $id)->update([
            'status' => self::STATUS_CANCELLED,
            'updated_at' => now(),
        ]);
        DB::table('after_sale_logs')->insert([
            'after_sale_id' => $id,
            'action' => '用户取消',
            'admin_id' => 0,
            'created_at' => now(),
        ]);
        return $this->success(null, '已取消');
    }
}
