<?php

namespace App\Modules\ShopCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopMenuController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('shop_menus');
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->has('keyword')) {
            $query->where('name', 'like', '%' . $request->input('keyword') . '%');
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $list = $query->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    public function tree(Request $request)
    {
        $type = $request->input('type', 'shop');
        $menus = DB::table('shop_menus')
            ->where('type', $type)
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->get();
        $tree = $this->buildTree($menus, 0);
        return $this->success($tree);
    }

    private function buildTree($menus, $parentId)
    {
        $tree = [];
        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $children = $this->buildTree($menus, $menu->id);
                $menu->children = $children;
                $tree[] = $menu;
            }
        }
        return $tree;
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
            'type' => 'nullable|string|in:shop,platform',
            'permission' => 'nullable|string|max:100',
        ]);
        $v['parent_id'] = $v['parent_id'] ?? 0;
        $v['sort'] = $v['sort'] ?? 0;
        $v['status'] = $v['status'] ?? 1;
        $v['type'] = $v['type'] ?? 'shop';
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('shop_menus')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'path' => 'sometimes|nullable|string|max:255',
            'icon' => 'sometimes|nullable|string|max:100',
            'parent_id' => 'sometimes|nullable|integer',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer',
            'type' => 'sometimes|string|in:shop,platform',
            'permission' => 'sometimes|nullable|string|max:100',
        ]);
        $v['updated_at'] = now();
        DB::table('shop_menus')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        $childCount = DB::table('shop_menus')->where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->error("该菜单下有{$childCount}个子菜单，请先删除子菜单");
        }
        DB::table('shop_menus')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
