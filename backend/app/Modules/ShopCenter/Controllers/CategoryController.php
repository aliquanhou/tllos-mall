<?php
namespace App\Modules\ShopCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('merchant_categories');

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $total = $query->count();
        $list = $query->orderBy('level', 'asc')
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function tree()
    {
        $all = DB::table('merchant_categories')
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $tree = $this->buildTree($all, 0);
        return $this->success($tree);
    }

    private function buildTree($items, $parentId)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = $this->buildTree($items, $item->id);
                $node = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'icon' => $item->icon,
                    'level' => $item->level,
                    'path' => $item->path,
                    'commission_rate' => $item->commission_rate,
                    'deposit' => $item->deposit,
                    'qualifications' => $item->qualifications ? json_decode($item->qualifications, true) : [],
                    'sort' => $item->sort,
                    'status' => $item->status,
                    'children' => $children,
                ];
                $tree[] = $node;
            }
        }
        return $tree;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:merchant_categories,name',
            'parent_id' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'deposit' => 'nullable|numeric|min:0',
            'qualifications' => 'nullable|array',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);

        $parentId = $validated['parent_id'] ?? 0;
        $level = 1;
        $path = '/';

        if ($parentId > 0) {
            $parent = DB::table('merchant_categories')->where('id', $parentId)->first();
            if (!$parent) {
                return $this->error('父级分类不存在');
            }
            $level = $parent->level + 1;
            if ($level > 3) {
                return $this->error('最多支持三级类目');
            }
            $path = $parent->path ? rtrim($parent->path, '/') . '/' . $parentId . '/' : '/' . $parentId . '/';
        }

        $data = [
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'level' => $level,
            'path' => $path,
            'icon' => $validated['icon'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? 0,
            'deposit' => $validated['deposit'] ?? 0,
            'qualifications' => isset($validated['qualifications']) ? json_encode($validated['qualifications'], JSON_UNESCAPED_UNICODE) : null,
            'sort' => $validated['sort'] ?? 0,
            'status' => $validated['status'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('merchant_categories')->insertGetId($data);
        return $this->success(['id' => $id, 'level' => $level, 'path' => $path], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $category = DB::table('merchant_categories')->where('id', $id)->first();
        if (!$category) return $this->error('分类不存在');

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'parent_id' => 'sometimes|integer',
            'icon' => 'sometimes|nullable|string|max:255',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'deposit' => 'sometimes|numeric|min:0',
            'qualifications' => 'sometimes|nullable|array',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $exists = DB::table('merchant_categories')->where('name', $validated['name'])->where('id', '!=', $id)->exists();
            if ($exists) return $this->error('分类名称已存在');
        }

        $data = $validated;
        if (isset($validated['qualifications'])) {
            $data['qualifications'] = json_encode($validated['qualifications'], JSON_UNESCAPED_UNICODE);
        }

        if (isset($validated['parent_id']) && $validated['parent_id'] != $category->parent_id) {
            $parentId = $validated['parent_id'];
            if ($parentId == $id) return $this->error('不能将自己设为父级');
            if ($parentId > 0) {
                $parent = DB::table('merchant_categories')->where('id', $parentId)->first();
                if (!$parent) return $this->error('父级分类不存在');
                $data['level'] = $parent->level + 1;
                if ($data['level'] > 3) return $this->error('最多支持三级类目');
                $data['path'] = $parent->path ? rtrim($parent->path, '/') . '/' . $parentId . '/' : '/' . $parentId . '/';
            } else {
                $data['level'] = 1;
                $data['path'] = '/';
            }
        }

        $data['updated_at'] = now();
        DB::table('merchant_categories')->where('id', $id)->update($data);
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        $category = DB::table('merchant_categories')->where('id', $id)->first();
        if (!$category) return $this->error('分类不存在');

        $children = DB::table('merchant_categories')->where('parent_id', $id)->count();
        if ($children > 0) {
            return $this->error('该分类下有' . $children . '个子分类，请先删除子分类');
        }

        $merchants = DB::table('merchants')->where('category_id', $id)->count();
        if ($merchants > 0) {
            return $this->error('该分类下有' . $merchants . '个商家，请先迁移商家');
        }

        DB::table('merchant_categories')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
