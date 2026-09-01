<?php
namespace App\Modules\Decorate\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TemplateController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('decorate_templates');
        if ($request->page_type) $query->where('page_type', $request->page_type);
        $list = $query->orderBy('id', 'asc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }
    public function show($id) {
        $template = DB::table('decorate_templates')->where('id', $id)->first();
        if (!$template) return $this->error('模板不存在');
        $template->components = json_decode($template->components, true);
        return $this->success($template);
    }
    public function store(Request $request) {
        $data = $request->only(['name', 'page_type', 'description', 'thumbnail', 'components']);
        if (is_array($data['components'] ?? null)) {
            $data['components'] = json_encode($data['components'], JSON_UNESCAPED_UNICODE);
        }
        $data['created_at'] = now();
        $id = DB::table('decorate_templates')->insertGetId($data);
        return $this->success(['id' => $id], '创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name', 'page_type', 'description', 'thumbnail', 'components', 'status']);
        if (is_array($data['components'] ?? null)) {
            $data['components'] = json_encode($data['components'], JSON_UNESCAPED_UNICODE);
        }
        $data['updated_at'] = now();
        DB::table('decorate_templates')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }
    public function destroy($id) {
        $template = DB::table('decorate_templates')->where('id', $id)->first();
        if ($template && $template->is_system) return $this->error('系统模板不能删除');
        DB::table('decorate_templates')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
