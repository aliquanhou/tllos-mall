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
        $shopId = $this->getShopId($request);
        $data = $request->only(['express_company','express_no']);
        $data['status'] = 2; $data['shipping_at'] = now(); $data['updated_at'] = now();
        DB::table('orders')->where('id',$id)->where('merchant_id',$shopId)->where('status',1)->update($data);
        return $this->success(null,'发货成功');
    }
}
