<?php
namespace App\Modules\ShopCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopCenterController extends BaseController
{
    public function categories() {
        $list = DB::table('shop_categories')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function categoryStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('shop_categories')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function categoryUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('shop_categories')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function categoryDestroy($id) {
        DB::table('shop_categories')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function banks($shop_id) {
        $list = DB::table('shop_banks')->where('shop_id',$shop_id)->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function accountLogs(Request $request) {
        $query = DB::table('shop_account_logs');
        if ($request->filled('keyword')) $query->where('shop_name','like','%'.$request->keyword.'%');
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
