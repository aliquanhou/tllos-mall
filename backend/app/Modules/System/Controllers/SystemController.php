<?php
namespace App\Modules\System\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends BaseController
{
    public function getConfig() {
        $configs = DB::table('system_configs')->pluck('value', 'key')->toArray();
        return $this->success($configs);
    }

    public function saveConfig(Request $request) {
        $data = $request->all();
        foreach ($data as $key => $value) {
            if (is_string($key) && !empty($key)) {
                DB::table('system_configs')->updateOrInsert(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]);
            }
        }
        return $this->success(null, '配置保存成功');
    }

    public function expressList(Request $request) {
        $query = DB::table('express_companies');
        if ($request->filled('keyword')) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->filled('status') && $request->status !== '') $query->where('status', $request->status);
        $total = $query->count();
        $list = $query->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function expressStore(Request $request) {
        $validated = $request->validate(['name' => 'required|string|max:50', 'code' => 'required|string|max:50', 'sort' => 'nullable|integer|default:0', 'status' => 'nullable|integer|default:1']);
        $validated['created_at'] = now(); $validated['updated_at'] = now();
        $id = DB::table('express_companies')->insertGetId($validated);
        return $this->success(['id' => $id], '添加成功');
    }

    public function expressUpdate(Request $request, $id) {
        $validated = $request->validate(['name' => 'sometimes|required|string|max:50', 'code' => 'sometimes|required|string|max:50', 'sort' => 'sometimes|nullable|integer', 'status' => 'sometimes|nullable|integer']);
        $validated['updated_at'] = now();
        DB::table('express_companies')->where('id', $id)->update($validated);
        return $this->success(null, '更新成功');
    }

    public function expressDestroy($id) {
        DB::table('express_companies')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    public function logList(Request $request) {
        $query = DB::table('admin_logs');
        if ($request->filled('keyword')) $query->where(function($q) use ($request) { $q->where('admin_name', 'like', '%'.$request->keyword.'%')->orWhere('module', 'like', '%'.$request->keyword.'%')->orWhere('content', 'like', '%'.$request->keyword.'%'); });
        if ($request->filled('module')) $query->where('module', $request->module);
        if ($request->filled('admin_id')) $query->where('admin_id', $request->admin_id);
        $total = $query->count();
        $page = $request->get('page', 1); $limit = $request->get('limit', 20);
        $list = $query->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
}
