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
        if ($request->filled('is_blacklisted')) {
            $query->where('m.is_blacklisted', $request->is_blacklisted);
        }

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('m.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        foreach ($list as &$item) {
            $item->is_overdue = ($item->status == 0 && strtotime($item->created_at) < strtotime('-3 days')) ? 1 : 0;
            $item->overdue_days = $item->is_overdue ? floor((time() - strtotime($item->created_at)) / 86400) : 0;
        }

        $stats = [
            'total' => DB::table('merchants')->whereNull('deleted_at')->count(),
            'pending' => DB::table('merchants')->where('status', 0)->whereNull('deleted_at')->count(),
            'approved' => DB::table('merchants')->where('status', 1)->whereNull('deleted_at')->count(),
            'rejected' => DB::table('merchants')->where('status', 2)->whereNull('deleted_at')->count(),
            'disabled' => DB::table('merchants')->where('status', 3)->whereNull('deleted_at')->count(),
            'overdue' => DB::table('merchants')->where('status', 0)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-3 days')))->whereNull('deleted_at')->count(),
            'blacklisted' => DB::table('merchants')->where('is_blacklisted', 1)->whereNull('deleted_at')->count(),
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

        $notifications = DB::table('merchant_notifications')
            ->where('merchant_id', $id)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $this->success(['merchant' => $merchant, 'audit_logs' => $auditLogs, 'notifications' => $notifications]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:100',
            'contact_name' => 'required|string|max:50',
            'contact_mobile' => 'required|string|max:20',
            'category_id' => 'required|exists:merchant_categories,id',
            'company_name' => 'nullable|string|max:100',
            'business_license' => 'nullable|string|max:255',
            'legal_person' => 'nullable|string|max:50',
            'id_card' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:50',
            'agreement_version' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if (!empty($validated['business_license'])) {
            if (!preg_match('/^[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}$/', $validated['business_license'])) {
                return $this->error('营业执照号格式不正确，应为18位统一社会信用代码');
            }
        }
        if (!empty($validated['id_card'])) {
            if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[0-9Xx]$/', $validated['id_card'])) {
                return $this->error('身份证号格式不正确');
            }
        }

        $exists = DB::table('merchants')
            ->where(function($q) use ($validated) {
                $q->where('contact_mobile', $validated['contact_mobile']);
                if (!empty($validated['business_license'])) {
                    $q->orWhere('business_license', $validated['business_license']);
                }
            })
            ->whereIn('status', [0, 1])
            ->whereNull('deleted_at')
            ->exists();
        if ($exists) {
            return $this->error('该手机号或营业执照已在平台入驻或审核中，请勿重复提交');
        }

        $blacklisted = DB::table('merchants')
            ->where('contact_mobile', $validated['contact_mobile'])
            ->where('is_blacklisted', 1)
            ->exists();
        if ($blacklisted) {
            return $this->error('该手机号已被列入黑名单，无法入驻');
        }

        $validated['username'] = $validated['contact_mobile'];
        $validated['password'] = Hash::make('admin123');
        $validated['status'] = 0;
        $validated['level'] = 1;
        $validated['agreement_signed_at'] = now();
        $validated['agreement_signed_ip'] = $request->ip();
        $validated['source'] = $validated['source'] ?? 'admin';
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        $id = DB::table('merchants')->insertGetId($validated);

        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'action' => 'submit',
            'before_status' => null,
            'after_status' => 0,
            'remark' => '商家提交入驻申请，来源:' . ($validated['source'] ?? 'admin'),
            'created_at' => now(),
        ]);

        return $this->success(['id' => $id], '入驻申请提交成功，等待审核');
    }

    public function draft(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:50',
            'contact_mobile' => 'nullable|string|max:20',
            'category_id' => 'nullable|integer',
            'company_name' => 'nullable|string|max:100',
            'business_license' => 'nullable|string|max:255',
            'legal_person' => 'nullable|string|max:50',
            'id_card' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $draft = [
            'data' => $validated,
            'saved_at' => now()->toDateTimeString(),
        ];

        $existing = DB::table('merchants')
            ->where('user_id', $validated['user_id'])
            ->where('status', -1)
            ->first();

        if ($existing) {
            DB::table('merchants')->where('id', $existing->id)->update([
                'draft_data' => json_encode($draft, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
            $id = $existing->id;
        } else {
            $id = DB::table('merchants')->insertGetId([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'] ?? '草稿_' . time(),
                'contact_name' => $validated['contact_name'] ?? '',
                'contact_mobile' => $validated['contact_mobile'] ?? '',
                'category_id' => $validated['category_id'] ?? 1,
                'username' => 'draft_' . time(),
                'password' => Hash::make('admin123'),
                'status' => -1,
                'level' => 1,
                'draft_data' => json_encode($draft, JSON_UNESCAPED_UNICODE),
                'province_id' => 0,
                'city_id' => 0,
                'district_id' => 0,
                'address' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success(['id' => $id, 'draft' => $draft], '草稿保存成功');
    }

    public function resubmit(Request $request, $id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) return $this->error('商家不存在');
        if ($merchant->status != 2) return $this->error('只有审核拒绝的商家才能重新提交');

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'contact_name' => 'sometimes|required|string|max:50',
            'contact_mobile' => 'sometimes|required|string|max:20',
            'category_id' => 'sometimes|required|integer',
            'company_name' => 'nullable|string|max:100',
            'business_license' => 'nullable|string|max:255',
            'legal_person' => 'nullable|string|max:50',
            'id_card' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $update = array_merge($validated, [
            'status' => 0,
            'reject_reason' => null,
            'approved_at' => null,
            'agreement_signed_at' => now(),
            'agreement_signed_ip' => $request->ip(),
            'updated_at' => now(),
        ]);
        DB::table('merchants')->where('id', $id)->update($update);

        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'action' => 'resubmit',
            'before_status' => 2,
            'after_status' => 0,
            'remark' => '商家重新提交入驻申请',
            'created_at' => now(),
        ]);

        return $this->success(null, '重新提交成功，等待审核');
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
        if ($merchant->is_blacklisted) return $this->error('该商家在黑名单中，无法通过审核');

        if (empty($merchant->agreement_signed_at)) {
            return $this->error('商家未签署入驻协议，无法审核');
        }

        $update = [
            'status' => $validated['status'],
            'approved_at' => now(),
            'updated_at' => now(),
        ];
        if ($validated['status'] == 2) {
            $update['reject_reason'] = $validated['reject_reason'] ?? '资料不符合要求';
            $update['reject_count'] = $merchant->reject_count + 1;
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
                    'shop_id' => $id, 'name' => '超级管理员',
                    'description' => '拥有所有权限', 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $exists = DB::table('shop_admins')->where('shop_id', $id)->where('username', $merchant->contact_mobile)->exists();
            if (!$exists) {
                DB::table('shop_admins')->insert([
                    'shop_id' => $id, 'username' => $merchant->contact_mobile,
                    'password' => Hash::make('admin123'), 'nickname' => $merchant->contact_name,
                    'role_id' => $roleId, 'mobile' => $merchant->contact_mobile,
                    'status' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('merchant_notifications')->insert([
                'merchant_id' => $id, 'type' => 'approve',
                'title' => '入驻审核通过',
                'content' => '恭喜！您的入驻申请已通过审核，登录账号：' . $merchant->contact_mobile . '，初始密码：admin123，请及时修改密码。',
                'created_at' => now(),
            ]);
        } else {
            DB::table('merchant_notifications')->insert([
                'merchant_id' => $id, 'type' => 'reject',
                'title' => '入驻审核拒绝',
                'content' => '您的入驻申请被拒绝，原因：' . ($validated['reject_reason'] ?? '资料不符合要求') . '。请修改资料后重新提交。',
                'created_at' => now(),
            ]);
            if (($merchant->reject_count + 1) >= 3) {
                DB::table('merchants')->where('id', $id)->update([
                    'is_blacklisted' => 1,
                    'blacklist_reason' => '累计拒绝' . ($merchant->reject_count + 1) . '次，自动拉黑',
                    'blacklist_at' => now(),
                ]);
            }
        }

        return $this->success(null, $validated['status'] == 1 ? '审核通过，店铺管理员账号已创建，通知已发送' : '已拒绝，通知已发送');
    }

    public function blacklist(Request $request, $id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) return $this->error('商家不存在');

        $action = $merchant->is_blacklisted ? 'unblacklist' : 'blacklist';
        $newStatus = $merchant->is_blacklisted ? 0 : 1;

        DB::table('merchants')->where('id', $id)->update([
            'is_blacklisted' => $newStatus,
            'blacklist_reason' => $newStatus ? ($request->input('reason', '违规操作') ) : null,
            'blacklist_at' => $newStatus ? now() : null,
            'status' => $newStatus ? 3 : $merchant->status,
            'updated_at' => now(),
        ]);

        DB::table('merchant_audit_logs')->insert([
            'merchant_id' => $id,
            'admin_id' => $request->user()?->id ?? 1,
            'action' => $action,
            'before_status' => $merchant->status,
            'after_status' => $newStatus ? 3 : $merchant->status,
            'remark' => $newStatus ? '加入黑名单：' . ($request->input('reason', '违规操作')) : '解除黑名单',
            'created_at' => now(),
        ]);

        return $this->success(null, $newStatus ? '已加入黑名单' : '已解除黑名单');
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

    public function rejectTemplates()
    {
        $list = DB::table('merchant_reject_templates')
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->get();
        return $this->success($list);
    }

    public function auditStats()
    {
        $stats = [
            'pending' => DB::table('merchants')->where('status', 0)->whereNull('deleted_at')->count(),
            'today_submitted' => DB::table('merchants')->whereDate('created_at', today())->whereNull('deleted_at')->count(),
            'today_approved' => DB::table('merchants')->where('status', 1)->whereDate('approved_at', today())->whereNull('deleted_at')->count(),
            'today_rejected' => DB::table('merchants')->where('status', 2)->whereDate('updated_at', today())->whereNull('deleted_at')->count(),
            'overdue' => DB::table('merchants')->where('status', 0)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-3 days')))->whereNull('deleted_at')->count(),
            'avg_audit_hours' => 0,
        ];

        $approved = DB::table('merchants')
            ->where('status', 1)
            ->whereNotNull('approved_at')
            ->whereNull('deleted_at')
            ->limit(50)
            ->get();
        if ($approved->count() > 0) {
            $totalHours = 0;
            foreach ($approved as $m) {
                $totalHours += max(0, (strtotime($m->approved_at) - strtotime($m->created_at)) / 3600);
            }
            $stats['avg_audit_hours'] = round($totalHours / $approved->count(), 1);
        }

        return $this->success($stats);
    }
}
