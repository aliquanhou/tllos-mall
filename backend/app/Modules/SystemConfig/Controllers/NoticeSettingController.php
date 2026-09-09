<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeSettingController extends BaseController {
    public function index(Request $request) {
        $settings = DB::table('notice_settings')->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function update(Request $request, $id) {
        foreach($request->all() as $k=>$v){
            if(is_string($k)&&!empty($k)) {
                DB::table('notice_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]);
            }
        }
        return $this->success(null,'保存成功');
    }
    public function save(Request $request) { return $this->update($request, 0); }
}
