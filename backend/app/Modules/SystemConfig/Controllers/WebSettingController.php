<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class WebSettingController extends BaseController {
    public function index(Request $request) {
        $settings = DB::table('web_settings')->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function save(Request $request) {
        foreach($request->all() as $k=>$v){
            if(is_string($k)&&!empty($k)) {
                DB::table('web_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]);
            }
        }
        return $this->success(null,'保存成功');
    }
    public function getWebsite() {
        $settings = DB::table('web_settings')->where('key', 'like', 'website_%')->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function getAgreement() {
        $settings = DB::table('web_settings')->where('key', 'like', 'agreement_%')->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function getCopyright() {
        $settings = DB::table('web_settings')->where('key', 'like', 'copyright_%')->pluck('value','key')->toArray();
        return $this->success($settings);
    }
}
