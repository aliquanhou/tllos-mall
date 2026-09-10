<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StorageConfigController extends BaseController {
    public function index() { return $this->success(DB::table('storage_configs')->orderBy('id','asc')->get()); }
    public function store(Request $request) {
        $data = $request->only(['platform','access_key','secret_key','bucket','region','domain','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('storage_configs')->insertGetId($data);
        return $this->success(DB::table('storage_configs')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['platform','access_key','secret_key','bucket','region','domain','status']);
        $data['updated_at'] = now();
        DB::table('storage_configs')->where('id',$id)->update($data);
        return $this->success(DB::table('storage_configs')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('storage_configs')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
