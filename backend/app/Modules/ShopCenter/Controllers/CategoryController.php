<?php
namespace App\Modules\ShopCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CategoryController extends BaseController {
    public function index(Request $request) {
        try {
            $list = DB::table('merchant_categories')->orderBy('sort','asc')->get();
            return $this->success(['list'=>$list,'total'=>count($list)]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function store(Request $request) {
        $data = $request->only(['name','sort','status']);
        $data['created_at'] = now();
        $id = DB::table('merchant_categories')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','sort','status']);
        DB::table('merchant_categories')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('merchant_categories')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
