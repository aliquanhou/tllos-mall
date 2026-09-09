<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageController extends BaseController
{
    public function lists() { $list = DB::table('storage_settings')->get(); return $this->success(['list'=>$list,'total'=>count($list)]); }
    public function detail() { $settings = DB::table('storage_settings')->pluck('value','key')->toArray(); return $this->success($settings); }
    public function setup(Request $request) { foreach($request->all() as $k=>$v){ if(is_string($k)&&!empty($k)) DB::table('storage_settings')->updateOrInsert(['key'=>$k],['value'=>$v,'updated_at'=>now()]); } return $this->success(null,'保存成功'); }
    public function change(Request $request) { $type=$request->input('storage_type','local'); DB::table('storage_settings')->updateOrInsert(['key'=>'storage_type'],['value'=>$type,'updated_at'=>now()]); return $this->success(['storage_type'=>$type],'切换成功'); }
}
