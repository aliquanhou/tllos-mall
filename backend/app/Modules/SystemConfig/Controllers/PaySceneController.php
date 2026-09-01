<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PaySceneController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('pay_scenes')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','code','pay_methods','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('pay_scenes')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
}
