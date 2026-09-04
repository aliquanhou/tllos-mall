<?php
namespace App\Modules\Permission\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OrganizationController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('organizations')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['parent_id','name','type','leader','sort','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('organizations')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
}
