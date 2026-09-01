<?php
namespace App\Modules\Pay\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaySceneController extends BaseController
{
    public function index() {
        $list = DB::table('pay_scenes')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function store(Request $request) {
        $v = $request->validate(['name'=>'required|string','code'=>'required|string','wechat_enabled'=>'nullable|integer','alipay_enabled'=>'nullable|integer','balance_enabled'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('pay_scenes')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','wechat_enabled'=>'sometimes|integer','alipay_enabled'=>'sometimes|integer','balance_enabled'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('pay_scenes')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('pay_scenes')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
