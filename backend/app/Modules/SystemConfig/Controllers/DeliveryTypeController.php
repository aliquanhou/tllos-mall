<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryTypeController extends BaseController
{
    public function index() { $list = DB::table('delivery_types')->orderBy('sort','asc')->get(); return $this->success(['list'=>$list,'total'=>count($list)]); }
    public function store(Request $request) { $v=$request->validate(['name'=>'required|string','type'=>'required|integer','fee'=>'nullable|numeric','free_amount'=>'nullable|numeric','sort'=>'nullable|integer','status'=>'nullable|integer']); $v['created_at']=now();$v['updated_at']=now(); $id=DB::table('delivery_types')->insertGetId($v); return $this->success(['id'=>$id],'创建成功'); }
    public function update(Request $request,$id) { $v=$request->validate(['name'=>'sometimes|required|string','fee'=>'sometimes|numeric','free_amount'=>'sometimes|numeric','sort'=>'sometimes|integer','status'=>'sometimes|integer']); $v['updated_at']=now(); DB::table('delivery_types')->where('id',$id)->update($v); return $this->success(null,'更新成功'); }
    public function destroy($id) { DB::table('delivery_types')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
