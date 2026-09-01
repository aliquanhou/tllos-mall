<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserCenterController extends BaseController {
    public function center(Request $request) {
        $user = $request->user();
        return $this->success(['user_info'=>$user,'order_count'=>0,'coupon_count'=>0,'collect_count'=>0,'balance'=>0,'points'=>0]);
    }
    public function info(Request $request) { return $this->success($request->user()); }
    public function updateInfo(Request $request) {
        $userId = $request->user()->id;
        $data = $request->only(['nickname','avatar','gender']);
        DB::table('users')->where('id',$userId)->update($data);
        return $this->success(null,'更新成功');
    }
    public function levels(Request $request) {
        try {
            $list = DB::table('user_levels')->orderBy('id','asc')->get();
            return $this->success(['list'=>$list,'total'=>count($list)]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function levelStore(Request $request) {
        $data = $request->only(['name','level','discount','points','status']);
        $data['created_at'] = now();
        $id = DB::table('user_levels')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function levelUpdate(Request $request, $id) {
        $data = $request->only(['name','level','discount','points','status']);
        DB::table('user_levels')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function levelDestroy($id) {
        DB::table('user_levels')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
