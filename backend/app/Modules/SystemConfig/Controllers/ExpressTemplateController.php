<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ExpressTemplateController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('express_templates')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','shipping_type','first_num','first_price','continue_num','continue_price','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('express_templates')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','shipping_type','first_num','first_price','continue_num','continue_price','status']);
        $data['updated_at'] = now();
        DB::table('express_templates')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('express_templates')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
