<?php
namespace App\Modules\Product\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsSkuController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('goods_sku');
        if($request->filled('goods_id')) $query->where('goods_id',$request->goods_id);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function store(Request $request) {
        $v = $request->validate(['goods_id'=>'required|integer','sku_name'=>'nullable|string','price'=>'nullable|numeric','stock'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('goods_sku')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $v = $request->validate(['price'=>'sometimes|numeric','stock'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('goods_sku')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) { DB::table('goods_sku')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
