<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class LevelController extends BaseController {
    public function index(Request $request) {
        try {
            $list = DB::table('user_levels')->orderBy('id','asc')->get();
            return $this->success(['list'=>$list,'total'=>count($list)]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function store(Request $request) {
        $data = $request->only(['name','level','discount','points','status']);
        $data['created_at'] = now();
        $id = DB::table('user_levels')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','level','discount','points','status']);
        DB::table('user_levels')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('user_levels')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
