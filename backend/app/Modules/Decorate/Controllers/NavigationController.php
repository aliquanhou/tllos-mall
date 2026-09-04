<?php
namespace App\Modules\Decorate\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class NavigationController extends BaseController {
    public function index() {
        $list = DB::table('navigations')->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['name', 'icon', 'link_type', 'link_id', 'link_url', 'sort', 'status']);
        $data['created_at'] = now();
        $id = DB::table('navigations')->insertGetId($data);
        return $this->success(['id' => $id], '创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name', 'icon', 'link_type', 'link_id', 'link_url', 'sort', 'status']);
        DB::table('navigations')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }
    public function destroy($id) {
        DB::table('navigations')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
