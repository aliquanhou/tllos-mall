<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryTypeController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('delivery_types')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','type','fee','free_amount','icon','sort','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('delivery_types')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','type','fee','free_amount','icon','sort','status']);
        $data['updated_at'] = now();
        DB::table('delivery_types')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('delivery_types')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
