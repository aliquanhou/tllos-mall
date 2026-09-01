<?php
namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        if ($request->filled('category_id')) {
            $query->where('m.category_id', $request->category_id);
        }
        if ($request->filled('level')) {
            $query->where('m.level', $request->level);
        }
        if ($request->filled('start_time')) {
            $query->where('m.created_at', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('m.created_at', '<=', $request->end_time);
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

        $auditLogs = DB::table('merchant_audit_logs')
            ->where('merchant_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success(['merchant' => $merchant, 'audit_logs' => $auditLogs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:100|unique:merchants,name',
            'contact_name' => 'required|string|max:50',
            'contact_mobile' => 'required|string|max:20',
            'category_id' => 'required|exists:merchant_categories,id',
            'company_name' => 'nullable|string|max:100',
            'business_license' => 'nullable|string|max:255',
            'legal_person' => 'nullable|string|max:50',
            'id_card' => 'nullable|string|max:30',
            'id_card_front' => 'nullable|string|max:255',
            'id_card_back' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:50',
            'qualification_images' => 'nullable|string',
            'agreement_version' => 'nullable|string|max:20',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['username'] = $validated['contact_mobile'];
        $validated['password'] = Hash::make('admin123');
        $validated['status'] = 0;
        $validated['level'] = 1;
        $validated['agreement_signed_at'] = now();
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        $id = DB::table('merchants')->insertGetId($validated);

        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'action' => 'submit',
            'before_status' => null,
            'after_status' => 0,
            'remark' => '商家提交入驻申请',
            'created_at' => now(),
        ]);

        return $this->success(['id' => $id], '入驻申请提交成功，等待审核');
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

        $adminId = $request->user()?->id ?? 1;
        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'admin_id' => $adminId,
            'action' => $validated['status'] == 1 ? 'approve' : 'reject',
            'before_status' => 0,
            'after_status' => $validated['status'],
            'remark' => $validated['status'] == 1 ? '审核通过' : ($validated['reject_reason'] ?? '审核拒绝'),
            'created_at' => now(),
        ]);

        if ($validated['status'] == 1) {
            $roleId = DB::table('shop_roles')->where('shop_id', $id)->where('name', '超级管理员')->value('id');
            if (!$roleId) {
                $roleId = DB::table('shop_roles')->insertGetId([
                    'shop_id' => $id,
                    'name' => '超级管理员',
                    'description' => '拥有所有权限',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $exists = DB::table('shop_admins')->where('shop_id', $id)->where('username', $merchant->contact_mobile)->exists();
            if (!$exists) {
                DB::table('shop_admins')->insert([
                    'shop_id' => $id,
                    'username' => $merchant->contact_mobile,
                    'password' => Hash::make('admin123'),
                    'nickname' => $merchant->contact_name,
                    'role_id' => $roleId,
                    'mobile' => $merchant->contact_mobile,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $this->success(null, $validated['status'] == 1 ? '审核通过，店铺管理员账号已创建' : '已拒绝');
    }

    public function toggleStatus($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) return $this->error('商家不存在');
        $newStatus = $merchant->status == 1 ? 3 : 1;
        DB::table('merchants')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);

        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'action' => $newStatus == 3 ? 'disable' : 'enable',
            'before_status' => $merchant->status,
            'after_status' => $newStatus,
            'remark' => $newStatus == 3 ? '平台禁用商家' : '解除禁用',
            'created_at' => now(),
        ]);

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
            'bank_name' => 'sometimes|nullable|string|max:100',
            'bank_account' => 'sometimes|nullable|string|max:50',
            'bank_account_name' => 'sometimes|nullable|string|max:50',
            'level' => 'sometimes|integer',
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
