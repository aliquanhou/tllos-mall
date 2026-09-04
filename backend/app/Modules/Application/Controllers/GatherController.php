<?php
namespace App\Modules\Application\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GatherController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('gathers')->orderBy('id','desc')->paginate($request->get('limit',20));
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','url','status']);
        $data['created_at'] = now();
        $id = DB::table('gathers')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','url','status']);
        $data['updated_at'] = now();
        DB::table('gathers')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('gathers')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function run($id) {
        DB::table('gathers')->where('id',$id)->update(['status'=>1,'updated_at'=>now()]);
        return $this->success(null,'采集任务已启动');
    }
}
