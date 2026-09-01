<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminGroupController extends BaseController {
    public function index(Request $request) {
        try {
            $list = DB::table('groups')->orderBy('id','desc')->paginate($request->get('limit',20));
            return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function store(Request $request) {
        $data = $request->only(['name','goods_id','group_price','group_num','start_time','end_time','status']);
        $data['created_at'] = now();
        $id = DB::table('groups')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','goods_id','group_price','group_num','start_time','end_time','status']);
        DB::table('groups')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('groups')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
