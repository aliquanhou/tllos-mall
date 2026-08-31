<?php
namespace App\Modules\ShopCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopPermissionController extends BaseController
{
    // 商家管理员
    public function adminList(Request $request) {
        $query = DB::table('shop_admins as sa')->leftJoin('shop_roles as sr','sa.role_id','=','sr.id')->select('sa.*','sr.name as role_name');
        if($request->filled('shop_id')) $query->where('sa.shop_id',$request->shop_id);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sa.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    // 商家角色
    public function roleList(Request $request) {
        $query = DB::table('shop_roles');
        if($request->filled('shop_id')) $query->where('shop_id',$request->shop_id);
        $list = $query->orderBy('id','desc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    // 商家部门
    public function deptList(Request $request) {
        $query = DB::table('shop_depts');
        if($request->filled('shop_id')) $query->where('shop_id',$request->shop_id);
        $list = $query->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    // 商家岗位
    public function jobList(Request $request) {
        $query = DB::table('shop_jobs');
        if($request->filled('shop_id')) $query->where('shop_id',$request->shop_id);
        $list = $query->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
}
