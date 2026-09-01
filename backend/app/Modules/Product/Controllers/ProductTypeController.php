<?php
namespace App\Modules\Product\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductTypeController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('product_types')->orderBy('sort','asc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','attributes','sort','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('product_types')->insertGetId($data);
        return $this->success(DB::table('product_types')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','attributes','sort','status']);
        $data['updated_at'] = now();
        DB::table('product_types')->where('id',$id)->update($data);
        return $this->success(DB::table('product_types')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('product_types')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
