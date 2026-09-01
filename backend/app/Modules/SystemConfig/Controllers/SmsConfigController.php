<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsConfigController extends BaseController
{
    public function detail() { $config = DB::table('sms_configs')->first(); return $this->success($config); }
    public function getConfig() { $config = DB::table('sms_configs')->first(); return $this->success($config); }
    public function setConfig(Request $request) { $v=$request->all(); $v['updated_at']=now(); $exists=DB::table('sms_configs')->first(); if($exists){DB::table('sms_configs')->where('id',$exists->id)->update($v);}else{DB::table('sms_configs')->insert($v);} return $this->success(null,'保存成功'); }
}
