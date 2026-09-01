<?php

namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantLevelController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('merchant_levels');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $list = $query->orderBy('sort', 'asc')->get();

        $stats = DB::table('merchants')
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->get();

        return $this->success(['list' => $list, 'stats' => $stats, 'total' => count($list)]);
    }

    public function show($id)
    {
        $level = DB::table('merchant_levels')->where('id', $id)->first();
        if (!$level) {
            return $this->error('等级不存在');
        }
        $merchantCount = DB::table('merchants')->where('level', $id)->count();
        $level->merchant_count = $merchantCount;
        return $this->success($level);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'benefits' => 'nullable|string',
            'upgrade_conditions' => 'nullable|string',
            'min_gmv' => 'nullable|numeric',
            'min_orders' => 'nullable|integer',
            'min_rating' => 'nullable|numeric',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);
        $v['created_at'] = now();
        $v['updated_at'] = now();
        $id = DB::table('merchant_levels')->insertGetId($v);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string',
            'commission_rate' => 'sometimes|nullable|numeric|min:0|max:100',
            'benefits' => 'sometimes|nullable|string',
            'upgrade_conditions' => 'sometimes|nullable|string',
            'min_gmv' => 'sometimes|nullable|numeric',
            'min_orders' => 'sometimes|nullable|integer',
            'min_rating' => 'sometimes|nullable|numeric',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer',
        ]);
        $v['updated_at'] = now();
        DB::table('merchant_levels')->where('id', $id)->update($v);
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        $merchantCount = DB::table('merchants')->where('level', $id)->count();
        if ($merchantCount > 0) {
            return $this->error("该等级下有{$merchantCount}个商家，请先调整商家等级");
        }
        DB::table('merchant_levels')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
