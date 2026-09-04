<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SmsConfigController extends BaseController {
    public function index() { return $this->success(DB::table('sms_configs')->orderBy('id','asc')->get()); }
    public function store(Request $request) {
        $data = $request->all();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('sms_configs')->insertGetId($data);
        return $this->success(['id'=>$id],'添加成功');
    }
    public function update(Request $request, $id) {
        $data = $request->all();
        $data['updated_at'] = now();
        DB::table('sms_configs')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) { DB::table('sms_configs')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
    public function getConfig() {
        $configs = DB::table('sms_configs')->orderBy('id','asc')->get();
        $result = [];
        foreach ($configs as $config) {
            $key = $config->key ?? $config->id;
            $result[$key] = $config;
        }
        return $this->success($result);
    }
}
