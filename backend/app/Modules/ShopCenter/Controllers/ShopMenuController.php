<?php
namespace App\Modules\ShopCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopMenuController extends BaseController
{
    public function index() { $list = DB::table('shop_menus')->orderBy('sort','asc')->get(); return $this->success(['list'=>$list,'total'=>count($list)]); }
    public function store(Request $request) { $v=$request->validate(['name'=>'required|string','path'=>'nullable|string','icon'=>'nullable|string','parent_id'=>'nullable|integer','sort'=>'nullable|integer','status'=>'nullable|integer']); $v['created_at']=now();$v['updated_at']=now(); $id=DB::table('shop_menus')->insertGetId($v); return $this->success(['id'=>$id],'创建成功'); }
    public function update(Request $request,$id) { $v=$request->validate(['name'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']); $v['updated_at']=now(); DB::table('shop_menus')->where('id',$id)->update($v); return $this->success(null,'更新成功'); }
    public function destroy($id) { DB::table('shop_menus')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
