<?php
namespace App\Modules\User\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAuthController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('user_auths as ua')->leftJoin('users as u','ua.user_id','=','u.id')->select('ua.*','u.mobile','u.nickname');
        if($request->filled('status')&&$request->status!=='') $query->where('ua.status',$request->status);
        if($request->filled('keyword')) $query->where(function($q)use($request){$q->where('ua.real_name','like','%'.$request->keyword.'%')->orWhere('ua.id_card','like','%'.$request->keyword.'%');});
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('ua.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function audit(Request $request, $id) {
        $v = $request->validate(['status'=>'required|integer|in:1,2','remark'=>'nullable|string']);
        DB::table('user_auths')->where('id',$id)->update(['status'=>$v['status'],'audit_time'=>now(),'audit_remark'=>$v['remark']??'','updated_at'=>now()]);
        return $this->success(null,$v['status']==1?'审核通过':'审核拒绝');
    }
}
