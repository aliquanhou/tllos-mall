<?php
namespace App\Modules\Permission\Controllers;
use App\Core\Controllers\BaseController;
use App\Modules\Permission\Models\Role;
use Illuminate\Http\Request;
class AdminRoleController extends BaseController
{
    public function index(Request $request)
    {
        $query = Role::query();
        if ($request->keyword) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->status !== null) $query->where('status', $request->status);
        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?? 20);
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'page' => $list->currentPage(), 'limit' => $list->perPage()]);
    }
    public function show($id)
    {
        $item = Role::find($id);
        if (!$item) return $this->error('角色管理不存在');
        return $this->success($item);
    }
    public function store(Request $request)
    {
        $item = Role::create($request->all());
        return $this->success($item, '创建成功');
    }
    public function update(Request $request, $id)
    {
        $item = Role::find($id);
        if (!$item) return $this->error('角色管理不存在');
        $item->update($request->all());
        return $this->success($item, '更新成功');
    }
    public function destroy($id)
    {
        $item = Role::find($id);
        if (!$item) return $this->error('角色管理不存在');
        $item->delete();
        return $this->success(null, '删除成功');
    }
}
