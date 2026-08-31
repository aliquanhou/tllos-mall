<?php
namespace App\Modules\System\Controllers;
use App\Core\Controllers\BaseController;
use App\Modules\System\Models\Setting;
use Illuminate\Http\Request;
class AdminSettingController extends BaseController
{
    public function index(Request $request)
    {
        $query = Setting::query();
        if ($request->keyword) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->status !== null) $query->where('status', $request->status);
        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?? 20);
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'page' => $list->currentPage(), 'limit' => $list->perPage()]);
    }
    public function show($id)
    {
        $item = Setting::find($id);
        if (!$item) return $this->error('系统设置不存在');
        return $this->success($item);
    }
    public function store(Request $request)
    {
        $item = Setting::create($request->all());
        return $this->success($item, '创建成功');
    }
    public function update(Request $request, $id)
    {
        $item = Setting::find($id);
        if (!$item) return $this->error('系统设置不存在');
        $item->update($request->all());
        return $this->success($item, '更新成功');
    }
    public function destroy($id)
    {
        $item = Setting::find($id);
        if (!$item) return $this->error('系统设置不存在');
        $item->delete();
        return $this->success(null, '删除成功');
    }
}
