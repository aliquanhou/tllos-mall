<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CollectController extends BaseController {
    public function lists(Request $request) {
        $userId = $request->user()->id;
        $type = $request->get('type','goods');
        $query = DB::table('goods_collects as gc')->join('products as p','gc.goods_id','=','p.id')->where('gc.user_id',$userId)->select('gc.*','p.name','p.main_image','p.price','p.sales');
        $list = $query->orderBy('gc.id','desc')->paginate($request->get('limit',20));
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function add(Request $request) {
        $userId = $request->user()->id;
        $goodsId = $request->input('goods_id');
        $exists = DB::table('goods_collects')->where('user_id',$userId)->where('goods_id',$goodsId)->exists();
        if ($exists) return $this->error('已收藏');
        DB::table('goods_collects')->insert(['user_id'=>$userId,'goods_id'=>$goodsId,'created_at'=>now()]);
        return $this->success(null,'收藏成功');
    }
    public function cancel(Request $request) {
        $userId = $request->user()->id;
        $goodsId = $request->input('goods_id');
        DB::table('goods_collects')->where('user_id',$userId)->where('goods_id',$goodsId)->delete();
        return $this->success(null,'取消成功');
    }
    public function delete(Request $request, $id) {
        DB::table('goods_collects')->where('id',$id)->where('user_id',$request->user()->id)->delete();
        return $this->success(null,'删除成功');
    }
}
