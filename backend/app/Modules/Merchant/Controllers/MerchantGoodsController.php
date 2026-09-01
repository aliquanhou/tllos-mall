<?php
namespace App\Modules\Merchant\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantGoodsController extends BaseController {
    private function getShopId($request) {
        return DB::table('shops')->where('user_id',$request->user()->id)->value('id') ?? 0;
    }
    public function lists(Request $request) {
        $shopId = $this->getShopId($request);
        $query = DB::table('products')->where('merchant_id',$shopId);
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        if ($request->filled('status')) $query->where('status',$request->status);
        $total = $query->count();
        $list = $query->orderBy('id','desc')->offset(($request->get('page',1)-1)*$request->get('limit',20))->limit($request->get('limit',20))->get();
        return $this->success(['list'=>$list,'total'=>$total]);
    }
    public function add(Request $request) {
        $shopId = $this->getShopId($request);
        $data = $request->only(['name','category_id','main_image','images','price','market_price','stock','description','is_hot','is_new','status']);
        $data['shop_id'] = $shopId; $data['created_at'] = now();
        $id = DB::table('products')->insertGetId($data);
        return $this->success(['id'=>$id],'添加成功');
    }
    public function edit(Request $request, $id) {
        $shopId = $this->getShopId($request);
        $data = $request->only(['name','category_id','main_image','images','price','market_price','stock','description','is_hot','is_new','status']);
        $data['updated_at'] = now();
        DB::table('products')->where('id',$id)->where('merchant_id',$shopId)->update($data);
        return $this->success(null,'修改成功');
    }
    public function delete(Request $request, $id) {
        $shopId = $this->getShopId($request);
        DB::table('products')->where('id',$id)->where('merchant_id',$shopId)->delete();
        return $this->success(null,'删除成功');
    }
    public function detail(Request $request, $id) {
        $shopId = $this->getShopId($request);
        $goods = DB::table('products')->where('id',$id)->where('merchant_id',$shopId)->first();
        return $this->success($goods);
    }
}
