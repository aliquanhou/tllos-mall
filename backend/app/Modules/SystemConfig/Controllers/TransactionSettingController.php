<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionSettingController extends BaseController
    public function index(Request $request) { return $this->getConfig(); }
{
    public function getConfig() { $settings = DB::table('transaction_settings')->pluck('value','key')->toArray(); return $this->success($settings); }
    public function setConfig(Request $request) { foreach($request->all() as $k=>$v){ if(is_string($k)&&!empty($k)) DB::table('transaction_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]); } return $this->success(null,'保存成功'); }
}
