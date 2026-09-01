<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PointLogController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('user_point_logs as l')->leftJoin('users as u','l.user_id','=','u.id');
        if ($request->user_id) $query->where('l.user_id', $request->user_id);
        if ($request->type) $query->where('l.type', $request->type);
        $list = $query->select('l.*','u.nickname','u.mobile')->orderBy('l.id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function store(Request $request) {
        $data = $request->only(['user_id','points','type','description']);
        $data['created_at'] = now();
        DB::table('user_point_logs')->insert($data);
        DB::table('users')->where('id',$request->user_id)->increment('points', $request->points);
        return $this->success(null,'积分调整成功');
    }
}
