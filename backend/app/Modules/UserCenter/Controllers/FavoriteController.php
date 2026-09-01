<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FavoriteController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('user_favorites as f')->leftJoin('users as u','f.user_id','=','u.id')->leftJoin('products as p','f.goods_id','=','p.id');
        if ($request->user_id) $query->where('f.user_id', $request->user_id);
        $list = $query->select('f.*','u.nickname','p.name as goods_name','p.main_image','p.price')->orderBy('f.id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function destroy($id) { DB::table('user_favorites')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
