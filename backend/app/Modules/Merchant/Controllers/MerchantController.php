<?php
namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('merchants as m')
            ->leftJoin('users as u', 'm.user_id', '=', 'u.id')
            ->select('m.*', 'u.nickname', 'u.mobile');

        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('m.name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('m.contact_name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('m.contact_mobile', 'like', '%' . $request->keyword . '%')
                  ->orWhere('m.company_name', 'like', '%' . $request->keyword . '%');
            });
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('m.status', $request->status);
        }

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('m.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $stats = [
            'total' => DB::table('merchants')->whereNull('deleted_at')->count(),
            'pending' => DB::table('merchants')->where('status', 0)->whereNull('deleted_at')->count(),
            'approved' => DB::table('merchants')->where('status', 1)->whereNull('deleted_at')->count(),
            'rejected' => DB::table('merchants')->where('status', 2)->whereNull('deleted_at')->count(),
            'disabled' => DB::table('merchants')->where('status', 3)->whereNull('deleted_at')->count(),
        ];

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit, 'stats' => $stats]);
    }

    public function show($id)
    {
        $merchant = DB::table('merchants as m')
            ->leftJoin('users as u', 'm.user_id', '=', 'u.id')
            ->select('m.*', 'u.nickname', 'u.mobile', 'u.email')
            ->where('m.id', $id)
            ->first();
        if (!$merchant) return $this->error('商家不存在');
        return $this->success($merchant);
    }

    public function audit(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:1,2',
            'reject_reason' => 'nullable|string|max:255',
        ]);
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) return $this->error('商家不存在');
        if ($merchant->status != 0) return $this->error('该商家已审核');

        $update = [
            'status' => $validated['status'],
            'approved_at' => now(),
            'updated_at' => now(),
        ];
        if ($validated['status'] == 2 && !empty($validated['reject_reason'])) {
            $update['reject_reason'] = $validated['reject_reason'];
        }
        DB::table('merchants')->where('id', $id)->update($update);
        return $this->success(null, $validated['status'] == 1 ? '审核通过' : '已拒绝');
    }

    public function toggleStatus($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) return $this->error('商家不存在');
        $newStatus = $merchant->status == 1 ? 3 : 1;
        DB::table('merchants')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);
        return $this->success(['status' => $newStatus], $newStatus == 1 ? '已启用' : '已禁用');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'contact_name' => 'sometimes|nullable|string|max:50',
            'contact_mobile' => 'sometimes|nullable|string|max:20',
            'company_name' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string|max:255',
        ]);
        $validated['updated_at'] = now();
        DB::table('merchants')->where('id', $id)->update($validated);
        return $this->success(null, '更新成功');
    }

    public function destroy($id)
    {
        DB::table('merchants')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->success(null, '删除成功');
    }
}
