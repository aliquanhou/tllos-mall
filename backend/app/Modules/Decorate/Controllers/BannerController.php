<?php
namespace App\Modules\Decorate\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BannerController extends BaseController {
    public function index() {
        $list = DB::table('banners')->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }
    public function store(Request $request) {
        $data = $request->only(['title', 'image', 'link_type', 'link_id', 'link_url', 'sort', 'status']);
        $data['created_at'] = now();
        $id = DB::table('banners')->insertGetId($data);
        return $this->success(['id' => $id], '创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['title', 'image', 'link_type', 'link_id', 'link_url', 'sort', 'status']);
        DB::table('banners')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }
    public function destroy($id) {
        DB::table('banners')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
