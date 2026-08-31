<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserCenterController extends BaseController
{
    public function levels() {
        $list = DB::table('user_levels')->orderBy('level','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function levelStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','level'=>'required|integer','discount'=>'required|numeric','description'=>'nullable|string','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('user_levels')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function levelUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','discount'=>'sometimes|required|numeric','description'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('user_levels')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function levelDestroy($id) {
        DB::table('user_levels')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function recharges(Request $request) {
        $query = DB::table('user_recharges');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('order_no','like','%'.$request->keyword.'%')->orWhere('user_name','like','%'.$request->keyword.'%');});
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function withdraws(Request $request) {
        $query = DB::table('user_withdraws');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('user_name','like','%'.$request->keyword.'%')->orWhere('account','like','%'.$request->keyword.'%');});
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function withdrawAudit(Request $request, $id) {
        $v = $request->validate(['status'=>'required|in:1,2','refuse_reason'=>'nullable|string']);
        DB::table('user_withdraws')->where('id',$id)->update(['status'=>$v['status'],'refuse_reason'=>$v['refuse_reason']??null,'audit_time'=>now(),'updated_at'=>now()]);
        return $this->success(null,$v['status']==1?'审核通过':'已拒绝');
    }
    public function withdrawPay($id) {
        DB::table('user_withdraws')->where('id',$id)->update(['status'=>3,'pay_time'=>now(),'updated_at'=>now()]);
        return $this->success(null,'已打款');
    }
    public function addresses(Request $request) {
        $query = DB::table('user_addresses');
        if ($request->filled('user_id')) $query->where('user_id',$request->user_id);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function accountLogs(Request $request) {
        $query = DB::table('account_logs');
        if ($request->filled('keyword')) $query->where('user_name','like','%'.$request->keyword.'%');
        if ($request->filled('type')) $query->where('type',$request->type);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
