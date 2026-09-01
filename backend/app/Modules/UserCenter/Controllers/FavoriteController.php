<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FavoriteController extends BaseController
{
    public function index(Request $request) {
        $q=DB::table('user_favorites as f')->leftJoin('users as u','f.user_id','=','u.id')->leftJoin('products as p','f.goods_id','=','p.id')->select('f.*','u.nickname','u.mobile','p.name as product_name','p.main_image','p.price','p.stock','p.status as product_status');
        if($request->filled('user_id'))$q->where('f.user_id',$request->user_id);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('p.name','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('f.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['total_favorites'=>$total,'total_users'=>DB::table('user_favorites')->distinct('user_id')->count('user_id')];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
    public function destroy($id) {
        DB::table('user_favorites')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}