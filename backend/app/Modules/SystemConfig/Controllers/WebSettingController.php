<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebSettingController extends BaseController
{
    public function getWebsite() { $settings = DB::table('web_settings')->whereIn('key',['website_name','website_logo','website_icp','copyright'])->pluck('value','key')->toArray(); return $this->success($settings); }
    public function setWebsite(Request $request) { foreach($request->all() as $k=>$v){ if(is_string($k)&&!empty($k)) DB::table('web_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]); } return $this->success(null,'保存成功'); }
    public function getAgreement() { $settings = DB::table('web_settings')->whereIn('key',['user_agreement','privacy_policy'])->pluck('value','key')->toArray(); return $this->success($settings); }
    public function setAgreement(Request $request) { foreach($request->all() as $k=>$v){ if(is_string($k)&&!empty($k)) DB::table('web_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]); } return $this->success(null,'保存成功'); }
    public function getCopyright() { $settings = DB::table('web_settings')->where('key','copyright')->pluck('value','key')->toArray(); return $this->success($settings); }
    public function setCopyright(Request $request) { DB::table('web_settings')->updateOrInsert(['key'=>'copyright'],['value'=>$request->input('copyright',''),'updated_at'=>now()]); return $this->success(null,'保存成功'); }
}
