<?php
namespace App\Modules\Permission\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends BaseController {
    // 菜单管理
    public function menuIndex(Request $request) {
        $list = DB::table('admin_menus')->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function menuStore(Request $request) {
        $data = $request->only(['parent_id','name','path','icon','component','type','sort','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('admin_menus')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function menuUpdate(Request $request, $id) {
        $data = $request->only(['parent_id','name','path','icon','component','type','sort','status']);
        $data['updated_at'] = now();
        DB::table('admin_menus')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function menuDestroy($id) {
        DB::table('admin_menus')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    // 部门管理
    public function deptIndex(Request $request) {
        $list = DB::table('departments')->orderBy('sort', 'asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function deptStore(Request $request) {
        $data = $request->only(['parent_id','name','leader','phone','sort','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('departments')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function deptUpdate(Request $request, $id) {
        $data = $request->only(['parent_id','name','leader','phone','sort','status']);
        $data['updated_at'] = now();
        DB::table('departments')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function deptDestroy($id) {
        DB::table('departments')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
