<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeSettingController extends BaseController
{
    public function index() { $list = DB::table('notice_settings')->orderBy('id','asc')->get(); return $this->success(['list'=>$list,'total'=>count($list)]); }
    public function update(Request $request,$id) { $v=$request->validate(['sms_enabled'=>'sometimes|integer','mp_enabled'=>'sometimes|integer','app_enabled'=>'sometimes|integer','content'=>'sometimes|nullable|string']); $v['updated_at']=now(); DB::table('notice_settings')->where('id',$id)->update($v); return $this->success(null,'更新成功'); }
}
