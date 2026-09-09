<?php

namespace App\Modules\ShopCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopPermissionController extends BaseController
{
    // ========== 管理员管理 ==========
    public function adminList(Request $request)
    {
        $query = DB::table('shop_admins as a')
            ->leftJoin('shop_roles as r', 'a.role_id', '=', 'r.id')
            ->leftJoin('shop_depts as d', 'a.dept_id', '=', 'd.id')
            ->leftJoin('shop_jobs as j', 'a.job_id', '=', 'j.id')
            ->select('a.id','a.shop_id','a.username','a.nickname','a.mobile','a.role_id','a.dept_id','a.job_id','a.status','a.last_login_at','a.created_at','a.updated_at', 'r.name as role_name', 'd.name as dept_name', 'j.name as job_name');

        if ($request->filled('shop_id')) {
            $query->where('a.shop_id', $request->shop_id);
        }
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('a.username', 'like', '%' . $request->keyword . '%')
                  ->orWhere('a.nickname', 'like', '%' . $request->keyword . '%')
                  ->orWhere('a.mobile', 'like', '%' . $request->keyword . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('a.status', $request->status);
        }

        $total = $query->count();
        $list = $query->orderBy('a.id', 'desc')->get();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function adminStore(Request $request)
    {
        $v = $request->validate([
            'shop_id' => 'required|integer',
            'username' => 'required|string|max:50|unique:shop_admins,username',
            'password' => 'required|string|min:6',
            'nickname' => 'nullable|string|max:50',
            'role_id' => 'nullable|integer',
            'dept_id' => 'nullable|integer',
            'job_id' => 'nullable|integer',
            'mobile' => 'nullable|string|max:20',
            'status' => 'nullable|integer',
        ]);
        $v['password'] = Hash::make($v['password']);
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('shop_admins')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function adminUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'nickname' => 'sometimes|nullable|string|max:50',
            'role_id' => 'sometimes|nullable|integer',
            'dept_id' => 'sometimes|nullable|integer',
            'job_id' => 'sometimes|nullable|integer',
            'mobile' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|integer',
            'password' => 'sometimes|nullable|string|min:6',
        ]);
        if (isset($v['password'])) {
            $v['password'] = Hash::make($v['password']);
        }
        $v['updated_at'] = now();
        DB::table('shop_admins')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function adminDestroy($id)
    {
        DB::table('shop_admins')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    // ========== 角色管理 ==========
    public function roleList(Request $request)
    {
        $query = DB::table('shop_roles');
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        $total = $query->count();
        $list = $query->orderBy('id', 'asc')->get();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function roleStore(Request $request)
    {
        $v = $request->validate([
            'shop_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|string',
            'status' => 'nullable|integer',
        ]);
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('shop_roles')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function roleUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string|max:255',
            'permissions' => 'sometimes|nullable|string',
            'status' => 'sometimes|integer',
        ]);
        $v['updated_at'] = now();
        DB::table('shop_roles')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function roleDestroy($id)
    {
        $adminCount = DB::table('shop_admins')->where('role_id', $id)->count();
        if ($adminCount > 0) {
            return $this->error("该角色下有{$adminCount}个管理员，请先调整管理员角色");
        }
        DB::table('shop_roles')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    // ========== 部门管理 ==========
    public function deptList(Request $request)
    {
        $query = DB::table('shop_depts');
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        $list = $query->orderBy('sort', 'asc')->get();
        $tree = $this->buildDeptTree($list, 0);
        return $this->success(['list' => $list, 'tree' => $tree, 'total' => count($list)]);
    }

    private function buildDeptTree($list, $parentId)
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item->parent_id == $parentId) {
                $children = $this->buildDeptTree($list, $item->id);
                $item->children = $children;
                $tree[] = $item;
            }
        }
        return $tree;
    }

    public function deptStore(Request $request)
    {
        $v = $request->validate([
            'shop_id' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);
        $v['parent_id'] = $v['parent_id'] ?? 0;
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('shop_depts')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function deptUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'parent_id' => 'sometimes|nullable|integer',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer',
        ]);
        $v['updated_at'] = now();
        DB::table('shop_depts')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function deptDestroy($id)
    {
        $childCount = DB::table('shop_depts')->where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->error("该部门下有{$childCount}个子部门，请先删除子部门");
        }
        $adminCount = DB::table('shop_admins')->where('dept_id', $id)->count();
        if ($adminCount > 0) {
            return $this->error("该部门下有{$adminCount}个管理员，请先调整管理员部门");
        }
        DB::table('shop_depts')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }

    // ========== 岗位管理 ==========
    public function jobList(Request $request)
    {
        $query = DB::table('shop_jobs');
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        $total = $query->count();
        $list = $query->orderBy('sort', 'asc')->get();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function jobStore(Request $request)
    {
        $v = $request->validate([
            'shop_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('shop_jobs')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function jobUpdate(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer',
        ]);
        $v['updated_at'] = now();
        DB::table('shop_jobs')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function jobDestroy($id)
    {
        $adminCount = DB::table('shop_admins')->where('job_id', $id)->count();
        if ($adminCount > 0) {
            return $this->error("该岗位下有{$adminCount}个管理员，请先调整管理员岗位");
        }
        DB::table('shop_jobs')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
