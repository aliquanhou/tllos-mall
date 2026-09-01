<?php
namespace App\Modules\User\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserAuthController extends BaseController
{
    public function index(Request $request) {
        $q=DB::table('user_real_names as r')->leftJoin('users as u','r.user_id','=','u.id')->select('r.*','u.nickname','u.mobile','u.avatar');
        if($request->filled('status'))$q->where('r.status',$request->status);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('r.real_name','like','%'.$request->keyword.'%')->orWhere('r.id_card','like','%'.$request->keyword.'%')->orWhere('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('r.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['pending'=>DB::table('user_real_names')->where('status',0)->count(),'approved'=>DB::table('user_real_names')->where('status',1)->count(),'rejected'=>DB::table('user_real_names')->where('status',2)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
    public function show($id) {
        $r=DB::table('user_real_names as r')->leftJoin('users as u','r.user_id','=','u.id')->select('r.*','u.nickname','u.mobile','u.avatar')->where('r.id',$id)->first();
        if(!$r)return $this->error('记录不存在');
        return $this->success($r);
    }
    public function audit(Request $request,$id) {
        $v=$request->validate(['status'=>'required|integer|in:1,2','audit_remark'=>'nullable|string']);
        $r=DB::table('user_real_names')->where('id',$id)->first();
        if(!$r)return $this->error('记录不存在');
        if($r->status!=0)return $this->error('该记录已审核');
        DB::table('user_real_names')->where('id',$id)->update(['status'=>$v['status'],'audit_remark'=>$v['audit_remark']??null,'audit_at'=>now(),'admin_id'=>$request->user()->id??1,'updated_at'=>now()]);
        return $this->success(null,$v['status']==1?'审核通过':'审核拒绝');
    }
}