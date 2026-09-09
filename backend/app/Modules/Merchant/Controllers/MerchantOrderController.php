<?php
namespace App\Modules\Merchant\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantOrderController extends BaseController {
    private function getShopId($request) {
        return DB::table('shops')->where('user_id',$request->user()->id)->value('id') ?? 0;
    }
    public function lists(Request $request) {
        $shopId = $this->getShopId($request);
        $query = DB::table('orders')->where('merchant_id',$shopId);
        if ($request->filled('order_no')) $query->where('order_no','like','%'.$request->order_no.'%');
        if ($request->filled('status')) $query->where('status',$request->status);
        $total = $query->count();
        $list = $query->orderBy('id','desc')->offset(($request->get('page',1)-1)*$request->get('limit',20))->limit($request->get('limit',20))->get();
        return $this->success(['list'=>$list,'total'=>$total]);
    }
    public function detail(Request $request, $id) {
        $shopId = $this->getShopId($request);
        $order = DB::table('orders')->where('id',$id)->where('merchant_id',$shopId)->first();
        $goods = DB::table('order_goods')->where('order_id',$id)->get();
        return $this->success(['order'=>$order,'goods'=>$goods]);
    }
    public function ship(Request $request, $id) {
        $request->validate([
            'express_company' => 'required|string|max:50',
            'express_no' => 'required|string|max:100',
        ]);

        $shopId = $this->getShopId($request);
        $order = DB::table('orders')->where('id', $id)->where('merchant_id', $shopId)->first();

        if (!$order) {
            return $this->error('订单不存在或无权操作', 404);
        }
        if ($order->status != 1) {
            return $this->error('当前订单状态不能发货，状态: ' . $order->status);
        }

        DB::table('orders')->where('id', $id)->update([
            'status' => 2,
            'express_company' => $request->express_company,
            'express_no' => $request->express_no,
            'ship_time' => now(),
            'auto_confirm_at' => now()->addDays(7),
            'updated_at' => now(),
        ]);

        // 记录订单日志
        DB::table('order_logs')->insert([
            'order_id' => $id,
            'order_no' => $order->order_no,
            'action' => 3,
            'action_name' => '发货',
            'operator_type' => 'merchant',
            'operator_id' => $shopId,
            'remark' => $request->express_company . ' 单号：' . $request->express_no,
            'created_at' => now(),
        ]);

        return $this->success(null, '发货成功');
    }
}
