<?php
namespace App\Modules\System\Controllers;

use App\Core\Controllers\BaseController;
use App\Core\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SensitiveWordController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('sensitive_words')->orderBy('id', 'desc');
        if ($request->keyword) $query->where('word', 'like', '%' . $request->keyword . '%');
        if ($request->category) $query->where('category', $request->category);
        $total = $query->count();
        $list = $query->offset(($request->page ?? 1) - 1)->limit($request->limit ?? 20)->get();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function store(Request $request)
    {
        $request->validate(['word' => 'required|string|unique:sensitive_words', 'category' => 'nullable|string', 'level' => 'nullable|integer']);
        $id = DB::table('sensitive_words')->insertGetId([
            'word' => $request->word, 'category' => $request->category ?? 'general',
            'level' => $request->level ?? 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        ContentModerationService::clearCache();
        return $this->success(['id' => $id], '添加成功');
    }

    public function update(Request $request, $id)
    {
        $word = DB::table('sensitive_words')->where('id', $id)->first();
        if (!$word) return $this->error('敏感词不存在', 404);
        $data = $request->only(['word', 'category', 'level', 'status']);
        $data['updated_at'] = now();
        DB::table('sensitive_words')->where('id', $id)->update($data);
        ContentModerationService::clearCache();
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        DB::table('sensitive_words')->where('id', $id)->delete();
        ContentModerationService::clearCache();
        return $this->success(null, '删除成功');
    }

    public function check(Request $request)
    {
        $request->validate(['content' => 'required|string']);
        $result = ContentModerationService::check($request->content);
        return $this->success($result);
    }
}
