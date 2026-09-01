<?php
namespace App\Modules\Distribute\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributionApplyController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('distribution_applies');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('user_name','like','%'.$request->keyword.'%')->orWhere('mobile','like','%'.$request->keyword.'%');});
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function audit(Request $request, $id) {
        $v = $request->validate(['status'=>'required|in:1,2','refuse_reason'=>'nullable|string']);
        DB::table('distribution_applies')->where('id',$id)->update(['status'=>$v['status'],'refuse_reason'=>$v['refuse_reason']??null,'audit_time'=>now(),'updated_at'=>now()]);
        return $this->success(null,$v['status']==1?'审核通过':'已拒绝');
    }
}
