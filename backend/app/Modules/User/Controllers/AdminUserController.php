<?php
namespace App\Modules\User\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class AdminUserController extends BaseController
{
    public function index(Request $request) {
        $q=DB::table('users as u')->leftJoin('user_levels as l','u.level_id','=','l.id')->select('u.*','l.name as level_name','l.discount as level_discount');
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%')->orWhere('u.account','like','%'.$request->keyword.'%');});
        if($request->filled('status'))$q->where('u.status',$request->status);
        if($request->filled('level_id'))$q->where('u.level_id',$request->level_id);
        if($request->filled('start_time'))$q->where('u.created_at','>=',$request->start_time);
        if($request->filled('end_time'))$q->where('u.created_at','<=',$request->end_time);
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('u.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['total_users'=>DB::table('users')->count(),'active_users'=>DB::table('users')->where('status',1)->count(),'disabled_users'=>DB::table('users')->where('status',0)->count(),'today_new'=>DB::table('users')->whereDate('created_at',date('Y-m-d'))->count(),'total_balance'=>DB::table('users')->sum('balance'),'total_points'=>DB::table('users')->sum('points')];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
    public function show($id) {
        $user=DB::table('users as u')->leftJoin('user_levels as l','u.level_id','=','l.id')->select('u.id','u.account','u.mobile','u.nickname','u.avatar','u.balance','u.points','u.level_id','u.status','u.created_at','u.updated_at','l.name as level_name')->where('u.id',$id)->first();
        if(!$user)return $this->error('用户不存在');
        $orders=DB::table('orders')->where('user_id',$id)->orderBy('id','desc')->limit(5)->get();
        $addresses=DB::table('user_addresses')->where('user_id',$id)->get();
        $favorites=DB::table('user_favorites as f')->leftJoin('products as p','f.goods_id','=','p.id')->where('f.user_id',$id)->select('f.*','p.name as product_name','p.main_image','p.price')->get();
        $balanceLogs=DB::table('user_balance_logs')->where('user_id',$id)->orderBy('id','desc')->limit(10)->get();
        $pointLogs=DB::table('user_point_logs')->where('user_id',$id)->orderBy('id','desc')->limit(10)->get();
        $realName=DB::table('user_real_names')->where('user_id',$id)->first();
        return $this->success(['user'=>$user,'orders'=>$orders,'addresses'=>$addresses,'favorites'=>$favorites,'balance_logs'=>$balanceLogs,'point_logs'=>$pointLogs,'real_name'=>$realName,'order_count'=>count($orders),'address_count'=>count($addresses),'favorite_count'=>count($favorites)]);
    }
    public function update($id,Request $request) {
        $v=$request->validate(['nickname'=>'sometimes|nullable|string|max:50','avatar'=>'sometimes|nullable|string','mobile'=>'sometimes|nullable|string|max:20','level_id'=>'sometimes|integer','balance'=>'sometimes|numeric','points'=>'sometimes|integer','status'=>'sometimes|integer','password'=>'sometimes|nullable|string|min:6']);
        if(isset($v['password']))$v['password']=Hash::make($v['password']);
        $v['updated_at']=now();
        DB::table('users')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function toggleStatus($id) {
        $user=DB::table('users')->where('id',$id)->first();
        if(!$user)return $this->error('用户不存在');
        $ns=$user->status==1?0:1;
        DB::table('users')->where('id',$id)->update(['status'=>$ns,'updated_at'=>now()]);
        return $this->success(['status'=>$ns],$ns==1?'已启用':'已禁用');
    }
}