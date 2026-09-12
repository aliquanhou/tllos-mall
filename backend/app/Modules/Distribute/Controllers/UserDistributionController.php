<?php
namespace App\Modules\Distribute\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserDistributionController extends BaseController
{
    /**
     * 分销中心首页
     */
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);

        $agent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status', 1)->first();
        if (!$agent) {
            return $this->success([
                'is_agent' => false,
                'message' => '您还不是分销商',
            ]);
        }

        $level = DB::table('distribute_levels')->where('id', $agent->level_id)->first();
        $pending = DB::table('distribute_orders')->where('agent_id', $agent->id)->where('status', 0)->sum('commission');
        $settled = DB::table('distribute_orders')->where('agent_id', $agent->id)->where('status', 1)->sum('commission');

        return $this->success([
            'is_agent' => true,
            'agent' => $agent,
            'level' => $level,
            'stats' => [
                'total_commission' => round($agent->total_commission, 2),
                'available_commission' => round($agent->available_commission, 2),
                'frozen_commission' => round($agent->frozen_commission, 2),
                'pending_commission' => round($pending, 2),
                'settled_commission' => round($settled, 2),
                'total_orders' => $agent->total_orders,
                'total_teams' => $agent->total_teams,
            ],
            'settings' => DB::table('distribute_settings')->pluck('value', 'key')->toArray(),
        ]);
    }

    public function applyStatus(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $apply = DB::table('distribution_applies')->where('user_id', $user->id)->orderBy('id','desc')->first();
        $isAgent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status',1)->first();
        return $this->success([
            'has_apply' => $apply ? true : false,
            'apply_status' => $apply->status ?? 0,
            'apply_info' => $apply,
            'is_agent' => $isAgent ? true : false,
            'agent_info' => $isAgent,
        ]);
    }

    public function submitApply(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $v = $request->validate([
            'wechat' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:500',
            'real_name' => 'nullable|string|max:50',
        ]);
        $existing = DB::table('distribution_applies')->where('user_id', $user->id)->where('status',0)->first();
        if ($existing) return $this->error('您已有待审核的申请，请等待审核');

        $applyFee = DB::table('distribute_settings')->where('key','apply_fee')->value('value') ?? 0;
        $applyType = DB::table('distribute_settings')->where('key','apply_type')->value('value') ?? 2;

        $id = DB::table('distribution_applies')->insertGetId([
            'user_id' => $user->id,
            'mobile' => $user->mobile,
            'user_name' => $v['real_name'] ?? ($user->nickname ?? ''),
            'wechat' => $v['wechat'] ?? '',
            'reason' => $v['reason'] ?? '',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 如果是免审模式，自动通过
        if ($applyType == 1) {
            DB::table('distribution_applies')->where('id', $id)->update(['status'=>1,'audit_time'=>now(),'updated_at'=>now()]);
            $this->createAgent($user->id, $v['real_name'] ?? '');
            return $this->success(['id'=>$id, 'auto_approved'=>true], '申请已通过，您现在是分销商了');
        }

        return $this->success(['id'=>$id], '申请已提交，请等待审核');
    }

    /**
     * 我的佣金明细
     */
    public function commissions(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $agent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status',1)->first();
        if (!$agent) return $this->error('您还不是分销商');

        $query = DB::table('distribute_orders')->where('agent_id', $agent->id);
        if ($request->filled('status') && $request->status !== '') $query->where('status', $request->status);

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 我的团队（下级）
     */
    public function team(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $agent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status',1)->first();
        if (!$agent) return $this->error('您还不是分销商');

        // 一级下线
        $firstLevel = DB::table('distribute_agents as da')
            ->leftJoin('users as u', 'da.user_id', '=', 'u.id')
            ->where('da.parent_id', $agent->id)
            ->where('da.status', 1)
            ->select('da.id', 'da.user_id', 'da.real_name', 'da.total_commission', 'da.total_orders', 'da.created_at', 'u.nickname', 'u.avatar')
            ->get();

        return $this->success([
            'first_level' => $firstLevel,
            'first_count' => $firstLevel->count(),
            'total_teams' => $agent->total_teams,
        ]);
    }

    /**
     * 推广链接
     */
    public function share(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $agent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status',1)->first();
        if (!$agent) return $this->error('您还不是分销商');

        $baseUrl = config('app.url');
        return $this->success([
            'share_url' => $baseUrl . '/?ref=' . $agent->id,
            'share_code' => (string)$agent->id,
            'agent_id' => $agent->id,
        ]);
    }

    /**
     * 可推广商品列表
     */
    public function goods(Request $request)
    {
        $query = DB::table('distribute_goods as dg')
            ->leftJoin('products as p', 'dg.product_id', '=', 'p.id')
            ->where('dg.status', 1)
            ->where('p.status', 1);

        if ($request->filled('keyword')) $query->where('p.name', 'like', '%'.$request->keyword.'%');

        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->select('dg.product_id', 'dg.product_name', 'p.name', 'p.main_image', 'p.price', 'dg.commission_type', 'dg.commission_rate', 'dg.commission_amount')
            ->orderBy('dg.sort', 'asc')->orderBy('dg.id', 'desc')
            ->offset(($page-1)*$limit)->limit($limit)->get();

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 提现申请
     */
    public function withdraw(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $agent = DB::table('distribute_agents')->where('user_id', $user->id)->where('status',1)->first();
        if (!$agent) return $this->error('您还不是分销商');

        $v = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:wechat,alipay,bank',
            'account' => 'required|string|max:100',
            'account_name' => 'required|string|max:50',
        ]);

        $minAmount = DB::table('distribute_settings')->where('key','withdraw_min')->value('value') ?? 10;
        if ($v['amount'] < $minAmount) return $this->error('提现金额不能低于' . $minAmount . '元');
        if ($v['amount'] > $agent->available_commission) return $this->error('可提现余额不足');

        DB::table('distribute_agents')->where('id', $agent->id)->update([
            'available_commission' => $agent->available_commission - $v['amount'],
            'frozen_commission' => $agent->frozen_commission + $v['amount'],
            'updated_at' => now(),
        ]);

        $id = DB::table('user_withdraws')->insertGetId([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'amount' => $v['amount'],
            'type' => $v['type'],
            'account' => $v['account'],
            'account_name' => $v['account_name'],
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], '提现申请已提交');
    }

    /**
     * 我的提现记录
     */
    public function withdraws(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);

        $query = DB::table('user_withdraws')->where('user_id', $user->id);
        $total = $query->count();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    /**
     * 自动创建分销商记录
     */
    private function createAgent($userId, $realName = '')
    {
        // 已是分销商则跳过
        $existing = DB::table('distribute_agents')->where('user_id', $userId)->first();
        if ($existing) return;

        $defaultLevel = DB::table('distribute_levels')->where('level', 1)->where('status', 1)->first();
        $user = DB::table('users')->where('id', $userId)->first();

        // 找上级（通过分享链接记录）
        $parentId = 0;
        $parentRef = DB::table('user_referrals')->where('user_id', $userId)->first();
        if ($parentRef) $parentId = $parentRef->agent_id ?? 0;

        DB::table('distribute_agents')->insert([
            'user_id' => $userId,
            'level_id' => $defaultLevel->id ?? 1,
            'parent_id' => $parentId,
            'real_name' => $realName ?: ($user->nickname ?? ''),
            'mobile' => $user->mobile ?? '',
            'status' => 1,
            'applied_at' => now(),
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
