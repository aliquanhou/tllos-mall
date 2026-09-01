<?php
namespace App\Modules\Product\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('categories');
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $categories = $query->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
        return $this->success(['list' => $categories, 'total' => $categories->count()]);
    }

    public function tree()
    {
        $categories = DB::table('categories')->where('status', 1)->orderBy('sort', 'asc')->get();
        $tree = $this->buildTree($categories->toArray());
        return $this->success($tree);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'parent_id' => 'nullable|integer|default:0',
            'icon' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|default:0',
            'status' => 'nullable|integer|default:1',
        ]);
        $parentId = $validated['parent_id'] ?? 0;
        $level = 1;
        if ($parentId > 0) {
            $parent = DB::table('categories')->where('id', $parentId)->first();
            $level = $parent ? $parent->level + 1 : 1;
        }
        $validated['level'] = $level;
        $validated['created_at'] = now();
        $validated['updated_at'] = now();
        $id = DB::table('categories')->insertGetId($validated);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'parent_id' => 'sometimes|nullable|integer',
            'icon' => 'sometimes|nullable|string|max:255',
            'image' => 'sometimes|nullable|string|max:255',
            'sort' => 'sometimes|nullable|integer',
            'status' => 'sometimes|nullable|integer',
        ]);
        $validated['updated_at'] = now();
        DB::table('categories')->where('id', $id)->update($validated);
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        $hasChildren = DB::table('categories')->where('parent_id', $id)->exists();
        if ($hasChildren) {
            return $this->error('该分类下有子分类，无法删除');
        }
        $hasProducts = DB::table('products')->where('category_id', $id)->exists();
        if ($hasProducts) {
            return $this->error('该分类下有商品，无法删除');
        }
        DB::table('categories')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    private function buildTree($categories, $parentId = 0)
    {
        $tree = [];
        foreach ($categories as $cat) {
            if ($cat->parent_id == $parentId) {
                $cat->children = $this->buildTree($categories, $cat->id);
                $tree[] = $cat;
            }
        }
        return $tree;
    }
}
