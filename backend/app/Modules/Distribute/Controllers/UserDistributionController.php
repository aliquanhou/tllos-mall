<?php
namespace App\Modules\Distribute\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserDistributionController extends BaseController
{
    public function applyStatus(Request $request) {
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

    public function submitApply(Request $request) {
        $user = Auth::guard('sanctum')->user();
        if (!$user) return $this->error('请先登录', 401);
        $v = $request->validate([
            'wechat' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:500',
        ]);
        $existing = DB::table('distribution_applies')->where('user_id', $user->id)->where('status',0)->first();
        if ($existing) return $this->error('您已有待审核的申请，请等待审核');
        $id = DB::table('distribution_applies')->insertGetId([
            'user_id' => $user->id,
            'mobile' => $user->mobile,
            'user_name' => $user->nickname ?? '',
            'wechat' => $v['wechat'] ?? '',
            'reason' => $v['reason'] ?? '',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->success(['id'=>$id], '申请已提交，请等待审核');
    }
}
