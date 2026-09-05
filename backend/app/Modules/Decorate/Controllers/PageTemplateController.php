<?php
namespace App\Modules\Decorate\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Decorate\Models\PageTemplate;
use App\Modules\Decorate\Models\PageTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PageTemplateController extends BaseController
{
    protected $cachePrefix = 'page_template:';

    // 清除指定页面的所有设备缓存
    protected function clearPageCache($slug)
    {
        foreach (['pc', 'tablet', 'mobile'] as $device) {
            Cache::forget($this->cachePrefix . $slug . ':' . $device);
        }
    }

    // 1. 页面列表（分页）
    public function index(Request $request)
    {
        $query = PageTemplate::query();
        if ($request->keyword) {
            $query->where('title', 'like', '%' . $request->keyword . '%')
                  ->orWhere('slug', 'like', '%' . $request->keyword . '%');
        }
        if ($request->is_published !== null) {
            $query->where('is_published', (int)$request->is_published);
        }
        $page = max(1, (int)($request->page ?? 1));
        $pageSize = min(50, max(1, (int)($request->page_size ?? 15)));
        $total = $query->count();
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get(['id', 'title', 'slug', 'version', 'is_published', 'is_default', 'updated_at']);
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    // 2. 页面详情（根据slug或id，返回config或draft_config）
    public function show(Request $request, $id)
    {
        $template = is_numeric($id)
            ? PageTemplate::find($id)
            : PageTemplate::where('slug', $id)->first();
        if (!$template) return $this->error('页面不存在');
        $useDraft = $request->draft == 1;
        $config = $useDraft ? $template->draft_config : $template->config;
        return $this->success([
            'id' => $template->id,
            'title' => $template->title,
            'slug' => $template->slug,
            'version' => $template->version,
            'is_published' => $template->is_published,
            'is_default' => $template->is_default,
            'config' => $config,
            'draft_config' => $template->draft_config,
            'updated_at' => $template->updated_at,
        ]);
    }

    // 3. 保存草稿（更新draft_config，不改变config，不生成版本）
    public function saveDraft(Request $request, $id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        $draftConfig = $request->input('config');
        if (!is_array($draftConfig)) {
            return $this->error('config必须是数组');
        }
        if (!isset($draftConfig['global']) || !isset($draftConfig['components'])) {
            return $this->error('config必须包含global和components字段');
        }
        $template->draft_config = $draftConfig;
        $template->save();
        $this->clearPageCache($template->slug);
        return $this->success(null, '草稿保存成功');
    }

    // 4. 发布页面（draft_config -> config，version+1，生成版本快照，清除缓存）
    public function publish(Request $request, $id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        if (!$template->draft_config) return $this->error('草稿为空，请先保存草稿');

        DB::beginTransaction();
        try {
            $newVersion = $template->version + 1;
            // 生成版本快照
            PageTemplateVersion::create([
                'template_id' => $template->id,
                'version' => $template->version,
                'config' => $template->config,
                'published_at' => $template->updated_at,
            ]);
            // 发布草稿
            $template->config = $template->draft_config;
            $template->version = $newVersion;
            $template->is_published = 1;
            $template->save();
            DB::commit();
            $this->clearPageCache($template->slug);
            return $this->success(['version' => $newVersion], '发布成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('发布失败: ' . $e->getMessage());
        }
    }

    // 5. 前端渲染（根据device过滤组件，合并全局样式，返回组件列表）
    public function render(Request $request, $slug)
    {
        $cacheKey = $this->cachePrefix . $slug . ':' . ($request->device ?? 'pc');
        $cached = Cache::get($cacheKey);
        if ($cached) return $this->success($cached);

        $template = PageTemplate::where('slug', $slug)->where('is_published', 1)->first();
        if (!$template) {
            // 找不到时返回默认首页
            $template = PageTemplate::where('is_default', 1)->where('is_published', 1)->first();
        }
        if (!$template) return $this->error('页面不存在');

        $config = $template->config;
        $device = $request->device ?? 'pc';
        $allowedDevices = ['pc', 'tablet', 'mobile'];
        if (!in_array($device, $allowedDevices)) $device = 'pc';

        // 过滤组件：按device可见性过滤
        $components = collect($config['components'] ?? [])
            ->filter(function ($comp) use ($device) {
                $visible = $comp['visible'] ?? ['pc' => true, 'tablet' => true, 'mobile' => true];
                return isset($visible[$device]) ? $visible[$device] : true;
            })
            ->sortBy('sort')
            ->values()
            ->toArray();

        $result = [
            'id' => $template->id,
            'title' => $template->title,
            'slug' => $template->slug,
            'version' => $template->version,
            'global' => $config['global'] ?? [],
            'components' => $components,
        ];
        Cache::put($cacheKey, $result, 3600);
        return $this->success($result);
    }

    // 6. 历史版本列表
    public function versions($id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        $versions = PageTemplateVersion::where('template_id', $id)
            ->orderBy('version', 'desc')
            ->get(['id', 'version', 'published_at', 'created_at']);
        return $this->success(['list' => $versions, 'current_version' => $template->version]);
    }

    // 7. 版本回滚（将指定版本的config恢复到当前页面的draft_config）
    public function rollback(Request $request, $id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        $versionId = (int)$request->input('version_id');
        $version = PageTemplateVersion::where('id', $versionId)
            ->where('template_id', $id)
            ->first();
        if (!$version) return $this->error('版本不存在');
        // 回滚到草稿，需要手动发布才生效
        $template->draft_config = $version->config;
        $template->save();
        $this->clearPageCache($template->slug);
        return $this->success(null, '已回滚到草稿，请确认后发布');
    }

    // 8. 导出配置（导出JSON文件）
    public function export($id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        $exportData = [
            'title' => $template->title,
            'slug' => $template->slug,
            'version' => $template->version,
            'exported_at' => now()->toDateTimeString(),
            'config' => $template->config,
        ];
        $json = json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $template->slug . '_v' . $template->version . '.json"',
        ]);
    }

    // 9. 导入配置（导入JSON创建新页面或覆盖当前页面）
    public function import(Request $request)
    {
        $file = $request->file('file');
        if (!$file) return $this->error('请上传JSON文件');
        if (!$file->isValid()) return $this->error('文件上传失败');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['config'])) {
            return $this->error('JSON格式不正确，必须包含config字段');
        }
        $overwriteId = (int)$request->input('overwrite_id', 0);
        DB::beginTransaction();
        try {
            if ($overwriteId > 0) {
                // 覆盖现有页面
                $template = PageTemplate::find($overwriteId);
                if (!$template) {
                    DB::rollBack();
                    return $this->error('要覆盖的页面不存在');
                }
                $template->draft_config = $data['config'];
                $template->save();
                $msg = '已导入到草稿，请确认后发布';
            } else {
                // 创建新页面
                $slug = $data['slug'] ?? ('page_' . time());
                $exists = PageTemplate::where('slug', $slug)->exists();
                if ($exists) $slug = $slug . '_' . time();
                $template = PageTemplate::create([
                    'title' => $data['title'] ?? '导入页面',
                    'slug' => $slug,
                    'config' => $data['config'],
                    'draft_config' => $data['config'],
                    'version' => 1,
                    'is_published' => 1,
                    'is_default' => 0,
                ]);
                $msg = '导入成功';
            }
            DB::commit();
            if (!empty($template->slug)) $this->clearPageCache($template->slug);
            return $this->success(['id' => $template->id, 'slug' => $template->slug], $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('导入失败: ' . $e->getMessage());
        }
    }

    // 创建页面
    public function store(Request $request)
    {
        $title = $request->input('title');
        $slug = $request->input('slug');
        if (!$title || !$slug) return $this->error('title和slug必填');
        $exists = PageTemplate::where('slug', $slug)->exists();
        if ($exists) return $this->error('slug已存在');
        $defaultConfig = [
            'global' => ['bg_color' => '#ffffff', 'font_family' => 'system-ui, sans-serif', 'custom_css' => ''],
            'components' => [],
        ];
        $template = PageTemplate::create([
            'title' => $title,
            'slug' => $slug,
            'config' => $defaultConfig,
            'draft_config' => $defaultConfig,
            'version' => 1,
            'is_published' => 0,
            'is_default' => 0,
        ]);
        return $this->success(['id' => $template->id], '创建成功');
    }

    // 删除页面
    public function destroy($id)
    {
        $template = PageTemplate::find($id);
        if (!$template) return $this->error('页面不存在');
        if ($template->is_default) return $this->error('默认首页不能删除');
        DB::beginTransaction();
        try {
            PageTemplateVersion::where('template_id', $id)->delete();
            $template->delete();
            DB::commit();
            $this->clearPageCache($template->slug);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }
}
