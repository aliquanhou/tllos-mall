<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PointLogController extends BaseController
{
    public function index(Request $request) {
        $q=DB::table('user_point_logs as p')->leftJoin('users as u','p.user_id','=','u.id')->select('p.*','u.nickname','u.mobile');
        if($request->filled('user_id'))$q->where('p.user_id',$request->user_id);
        if($request->filled('type'))$q->where('p.type',$request->type);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('p.description','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('p.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['total_points'=>DB::table('user_point_logs')->sum('points'),'total_count'=>DB::table('user_point_logs')->count(),'today_points'=>DB::table('user_point_logs')->whereDate('created_at',date('Y-m-d'))->sum('points')];
        $rules=DB::table('point_rules')->where('status',1)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats,'rules'=>$rules]);
    }
    public function store(Request $request) {
        $v=$request->validate(['user_id'=>'required|integer','points'=>'required|integer','type'=>'required|string|max:50','description'=>'nullable|string']);
        $v['created_at']=now();
        $id=DB::table('user_point_logs')->insertGetId($v);
        DB::table('users')->where('id',$v['user_id'])->increment('points',$v['points']);
        return $this->success(['id'=>$id],'积分调整成功');
    }
    public function rules(Request $request) {
        $list=DB::table('point_rules')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
}