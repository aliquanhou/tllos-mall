<?php
namespace App\Modules\Permission\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PermissionController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('admin_menus')->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
        return $this->success($list);
    }
    public function tree() {
        $list = DB::table('admin_menus')->where('status', 1)->orderBy('sort', 'asc')->get();
        return $this->success($this->buildTree($list));
    }
    private function buildTree($list, $parentId = 0) {
        $tree = [];
        foreach ($list as $item) {
            if ($item->parent_id == $parentId) {
                $item->children = $this->buildTree($list, $item->id);
                $tree[] = $item;
            }
        }
        return $tree;
    }
    public function store(Request $request) {
        $data = $request->only(['parent_id','name','path','icon','component','sort','status','type']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('admin_menus')->insertGetId($data);
        return $this->success(DB::table('admin_menus')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['parent_id','name','path','icon','component','sort','status','type']);
        $data['updated_at'] = now();
        DB::table('admin_menus')->where('id',$id)->update($data);
        return $this->success(DB::table('admin_menus')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) {
        DB::table('admin_menus')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
