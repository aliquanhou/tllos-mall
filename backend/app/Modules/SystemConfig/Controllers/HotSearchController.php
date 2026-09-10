<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class HotSearchController extends BaseController {
    public function index() {
        return $this->success(DB::table('hot_searches')->orderBy('sort','asc')->get());
    }
    public function store(Request $request) {
        $data = $request->only(['keyword','sort','status']);
        $data['created_at'] = now();
        $id = DB::table('hot_searches')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['keyword','sort','status']);
        DB::table('hot_searches')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('hot_searches')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
