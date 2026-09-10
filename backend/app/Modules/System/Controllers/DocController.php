<?php
namespace App\Modules\System\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DocController extends BaseController {
    public function show($module, $page = '_index') {
        $module = preg_replace('/[^a-zA-Z0-9_-]/', '', $module);
        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
        $path = resource_path("docs/modules/{$module}/{$page}.md");
        if (!File::exists($path)) {
            $globalPath = resource_path("docs/_global/{$module}.md");
            if (File::exists($globalPath)) {
                return $this->success(['module'=>$module,'page'=>$module,'content'=>File::get($globalPath),'source'=>'global']);
            }
            return $this->error("文档不存在: {$module}/{$page}.md", 404);
        }
        return $this->success(['module'=>$module,'page'=>$page,'content'=>File::get($path),'source'=>'module','updated_at'=>date('Y-m-d H:i:s',File::lastModified($path))]);
    }
    public function list($module) {
        $module = preg_replace('/[^a-zA-Z0-9_-]/', '', $module);
        $dir = resource_path("docs/modules/{$module}");
        if (!File::isDirectory($dir)) return $this->error("模块不存在: {$module}", 404);
        $docs = [];
        foreach (File::files($dir) as $f) {
            $docs[] = ['name'=>$f->getFilenameWithoutExtension(),'path'=>"{$module}/".$f->getFilenameWithoutExtension(),'size'=>$f->getSize()];
        }
        return $this->success(['module'=>$module,'docs'=>$docs,'total'=>count($docs)]);
    }
}
