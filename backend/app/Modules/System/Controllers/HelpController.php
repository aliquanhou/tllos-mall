<?php
namespace App\Modules\System\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelpController extends BaseController {
    public function show($module, $page = '_index') {
        $module = preg_replace('/[^a-zA-Z0-9_-]/', '', $module);
        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
        
        $doc = DB::table('help_docs')
            ->where('module', $module)
            ->where('page', $page)
            ->where('status', 'published')
            ->first();
        
        if (!$doc) {
            // 尝试加载父级文档
            $doc = DB::table('help_docs')
                ->where('module', $module)
                ->where('page', '_index')
                ->where('status', 'published')
                ->first();
            if (!$doc) {
                return $this->error("帮助文档不存在: {$module}/{$page}", 404);
            }
        }
        
        $content = json_decode($doc->content, true);
        if (!$content) {
            $content = ['overview' => $doc->content, 'api' => [], 'fields' => [], 'relations' => [], 'status' => [], 'quick_actions' => []];
        }
        
        return $this->success([
            'id' => $doc->id,
            'module' => $doc->module,
            'page' => $doc->page,
            'title' => $doc->title,
            'content' => $content,
            'version' => $doc->version,
            'updated_at' => $doc->updated_at
        ]);
    }
    
    public function update(Request $request, $module, $page = '_index') {
        $module = preg_replace('/[^a-zA-Z0-9_-]/', '', $module);
        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
        
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|array',
            'status' => 'in:draft,published',
        ]);
        
        $existing = DB::table('help_docs')->where('module', $module)->where('page', $page)->first();
        
        if ($existing) {
            DB::table('help_docs')->where('id', $existing->id)->update([
                'title' => $data['title'],
                'content' => json_encode($data['content'], JSON_UNESCAPED_UNICODE),
                'status' => $data['status'] ?? 'published',
                'version' => $existing->version + 1,
                'updated_at' => now()
            ]);
        } else {
            DB::table('help_docs')->insert([
                'module' => $module,
                'page' => $page,
                'title' => $data['title'],
                'content' => json_encode($data['content'], JSON_UNESCAPED_UNICODE),
                'status' => $data['status'] ?? 'published',
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return $this->success(null, '保存成功');
    }
    
    public function list($module) {
        $module = preg_replace('/[^a-zA-Z0-9_-]/', '', $module);
        $docs = DB::table('help_docs')->where('module', $module)->get(['id', 'page', 'title', 'status', 'version', 'updated_at']);
        return $this->success(['module' => $module, 'docs' => $docs, 'total' => count($docs)]);
    }
}
