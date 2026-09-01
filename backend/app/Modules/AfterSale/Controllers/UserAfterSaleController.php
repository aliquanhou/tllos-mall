<?php
namespace App\Modules\AfterSale\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserAfterSaleController extends BaseController {
    public function lists(Request $request) {
        $userId = $request->user()->id;
        $list = DB::table('order_after_sales')->where('user_id',$userId)->orderBy('id','desc')->paginate($request->get('limit',20));
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function add(Request $request) {
        $userId = $request->user()->id;
        $data = $request->only(['order_id','order_goods_id','type','reason','amount','images','description']);
        $data['user_id'] = $userId; $data['status'] = 0; $data['created_at'] = now();
        $id = DB::table('order_after_sales')->insertGetId($data);
        return $this->success(['id'=>$id],'申请成功');
    }
    public function detail(Request $request, $id) {
        $afterSale = DB::table('order_after_sales')->where('id',$id)->where('user_id',$request->user()->id)->first();
        return $this->success($afterSale);
    }
    public function cancel(Request $request, $id) {
        DB::table('order_after_sales')->where('id',$id)->where('user_id',$request->user()->id)->where('status',0)->update(['status'=>5,'updated_at'=>now()]);
        return $this->success(null,'已取消');
    }
}
