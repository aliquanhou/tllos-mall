<?php
namespace App\Modules\Decorate\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PageController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('decorate_pages');
        if ($request->page_type) $query->where('page_type', $request->page_type);
        $list = $query->orderBy('id', 'asc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }
    public function show($id) {
        $page = DB::table('decorate_pages')->where('id', $id)->first();
        if (!$page) return $this->error('页面不存在');
        $components = DB::table('decorate_components')->where('page_id', $id)->orderBy('sort', 'asc')->get();
        return $this->success(['page' => $page, 'components' => $components]);
    }
    public function store(Request $request) {
        $data = $request->only(['name', 'page_type', 'template_id', 'status']);
        $data['created_at'] = now();
        $id = DB::table('decorate_pages')->insertGetId($data);
        return $this->success(['id' => $id], '创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name', 'page_type', 'template_id', 'status']);
        $data['updated_at'] = now();
        DB::table('decorate_pages')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }
    public function destroy($id) {
        DB::table('decorate_pages')->where('id', $id)->delete();
        DB::table('decorate_components')->where('page_id', $id)->delete();
        return $this->success(null, '删除成功');
    }
    // 保存页面组件配置
    public function saveComponents(Request $request, $id) {
        $components = $request->input('components', []);
        DB::table('decorate_components')->where('page_id', $id)->delete();
        foreach ($components as $sort => $comp) {
            DB::table('decorate_components')->insert([
                'page_id' => $id,
                'component_type' => $comp['type'] ?? $comp['component_type'],
                'name' => $comp['name'] ?? '',
                'sort' => $sort,
                'config' => json_encode($comp['config'] ?? $comp, JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $this->success(null, '保存成功');
    }
    // 应用模板
    public function applyTemplate(Request $request, $id) {
        $templateId = $request->input('template_id');
        $template = DB::table('decorate_templates')->where('id', $templateId)->first();
        if (!$template) return $this->error('模板不存在');
        $components = json_decode($template->components, true);
        DB::table('decorate_components')->where('page_id', $id)->delete();
        foreach ($components as $sort => $comp) {
            DB::table('decorate_components')->insert([
                'page_id' => $id,
                'component_type' => $comp['type'],
                'name' => $comp['name'] ?? '',
                'sort' => $sort,
                'config' => json_encode($comp['config'] ?? [], JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'created_at' => now(),
            ]);
        }
        DB::table('decorate_pages')->where('id', $id)->update(['template_id' => $templateId, 'updated_at' => now()]);
        return $this->success(null, '模板应用成功');
    }
}
