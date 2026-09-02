<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserCenterController extends BaseController
{
    public function center(Request $request) {
        $user = $request->user();
        return $this->success(['user'=>$user,'order_count'=>DB::table('orders')->where('user_id',$user->id)->count(),'favorite_count'=>DB::table('user_favorites')->where('user_id',$user->id)->count()]);
    }
    public function info(Request $request) { return $this->success($request->user()); }
    public function updateInfo(Request $request) {
        $v=$request->validate(['nickname'=>'sometimes|nullable|string|max:50','avatar'=>'sometimes|nullable|string','email'=>'sometimes|nullable|email']);
        DB::table('users')->where('id',$request->user()->id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function levels(Request $request) {
        $list=DB::table('user_levels')->orderBy('level','asc')->get();
        $stats=DB::table('users')->select('level_id',DB::raw('COUNT(*) as count'))->groupBy('level_id')->get();
        return $this->success(['list'=>$list,'stats'=>$stats,'total'=>count($list)]);
    }
    public function levelStore(Request $request) {
        $v=$request->validate(['name'=>'required|string|max:100','level'=>'nullable|integer','discount'=>'nullable|numeric','points'=>'nullable|integer','benefits'=>'nullable|string','upgrade_points'=>'nullable|integer','is_default'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now();$v['updated_at']=now();
        return $this->success(['id'=>DB::table('user_levels')->insertGetId($v)],'创建成功');
    }
    public function levelUpdate(Request $request,$id) {
        $v=$request->validate(['name'=>'sometimes|required|string|max:100','level'=>'sometimes|integer','discount'=>'sometimes|numeric','points'=>'sometimes|integer','benefits'=>'sometimes|nullable|string','upgrade_points'=>'sometimes|integer','is_default'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('user_levels')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function levelDestroy($id) {
        $c=DB::table('users')->where('level_id',$id)->count();
        if($c>0) return $this->error("该等级下有{$c}个用户");
        DB::table('user_levels')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function recharges(Request $request) {
        $q=DB::table('user_recharges as r')->leftJoin('users as u','r.user_id','=','u.id')->select('r.*','u.nickname','u.mobile');
        if($request->filled('status'))$q->where('r.status',$request->status);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%')->orWhere('r.pay_no','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('r.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['total_amount'=>DB::table('user_recharges')->where('status',1)->sum('amount'),'total_count'=>DB::table('user_recharges')->where('status',1)->count(),'pending_count'=>DB::table('user_recharges')->where('status',0)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
    public function rechargeConfirm(Request $request, $id) {
        $v = $request->validate([
            'remark' => 'nullable|string|max:255',
            'pay_type' => 'nullable|string|max:50',
            'pay_no' => 'nullable|string|max:100',
        ]);
        $recharge = DB::table('user_recharges')->where('id', $id)->first();
        if (!$recharge) return $this->error('充值记录不存在');
        if ($recharge->status == 1) return $this->error('该充值单已支付，无需补单');
        if ($recharge->status != 0) return $this->error('该充值单状态不允许补单');

        $admin = $request->user();
        $now = now();

        DB::beginTransaction();
        try {
            // 1. 更新充值单状态
            DB::table('user_recharges')->where('id', $id)->update([
                'status' => 1,
                'paid_at' => $now,
                'admin_id' => $admin->id ?? null,
                'pay_type' => $v['pay_type'] ?? $recharge->pay_type,
                'pay_no' => $v['pay_no'] ?? $recharge->pay_no,
                'remark' => $v['remark'] ?? $recharge->remark,
                'updated_at' => $now,
            ]);

            // 2. 增加用户余额（充值金额+赠送金额）
            $totalAmount = $recharge->amount + $recharge->give_amount;
            $balanceBefore = DB::table('users')->where('id', $recharge->user_id)->value('balance');
            DB::table('users')->where('id', $recharge->user_id)->increment('balance', $totalAmount);
            $balanceAfter = $balanceBefore + $totalAmount;

            // 3. 记录余额变动日志
            DB::table('user_balance_logs')->insert([
                'user_id' => $recharge->user_id,
                'type' => 1, // 1=充值
                'amount' => $totalAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remark' => '人工补单，充值单号ID:' . $id . '，充值' . $recharge->amount . '元，赠送' . $recharge->give_amount . '元',
                'created_at' => $now,
            ]);

            // 4. 发送通知给用户
            DB::table('user_notifications')->insert([
                'user_id' => $recharge->user_id,
                'title' => '充值到账通知',
                'content' => '您的充值订单（金额：' . $recharge->amount . '元，赠送：' . $recharge->give_amount . '元）已到账，当前余额已增加' . $totalAmount . '元。',
                'type' => 'system',
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();
            return $this->success(['id' => $id, 'total_amount' => $totalAmount], '补单成功，用户余额已增加' . $totalAmount . '元');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('补单失败：' . $e->getMessage());
        }
    }

    public function withdraws(Request $request) {
        $q=DB::table('withdraws as w')->leftJoin('users as u','w.user_id','=','u.id')->select('w.*','u.nickname','u.mobile');
        if($request->filled('status'))$q->where('w.status',$request->status);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%')->orWhere('w.pay_account','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('w.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=['pending_count'=>DB::table('withdraws')->where('status',0)->count(),'pending_amount'=>DB::table('withdraws')->where('status',0)->sum('actual_amount'),'paid_count'=>DB::table('withdraws')->where('status',3)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
    public function withdrawAudit(Request $request,$id) {
        $v=$request->validate(['status'=>'required|integer|in:1,2','audit_remark'=>'nullable|string']);
        $w=DB::table('withdraws')->where('id',$id)->first();
        if(!$w)return $this->error('提现记录不存在');
        if($w->status!=0)return $this->error('该提现已处理');
        $up=['status'=>$v['status'],'audit_remark'=>$v['audit_remark']??null,'audit_at'=>now(),'admin_id'=>$request->user()->id??1];
        if($v['status']==2){DB::table('users')->where('id',$w->user_id)->increment('balance',$w->amount);}
        DB::table('withdraws')->where('id',$id)->update($up);
        return $this->success(null,$v['status']==1?'审核通过':'审核拒绝');
    }
    public function withdrawPay(Request $request,$id) {
        $w=DB::table('withdraws')->where('id',$id)->first();
        if(!$w)return $this->error('提现记录不存在');
        if($w->status!=1)return $this->error('该提现未通过审核或已打款');
        $adminId = $request->user()->id ?? 1;
        $isFail = $request->input('is_fail', 0);
        $failureReason = $request->input('failure_reason', '');
        if ($isFail) {
            DB::table('withdraws')->where('id',$id)->update([
                'failure_reason' => $failureReason ?: '第三方支付接口调用失败',
                'retry_count' => DB::raw('retry_count + 1'),
                'admin_id' => $adminId,
                'updated_at' => now(),
            ]);
            return $this->error('打款失败：' . ($failureReason ?: '第三方支付接口调用失败'));
        }
        DB::table('withdraws')->where('id',$id)->update([
            'status'=>3,
            'paid_at'=>now(),
            'pay_no'=>'PAY'.date('YmdHis').rand(1000,9999),
            'failure_reason'=>null,
            'admin_id'=>$adminId,
            'updated_at'=>now(),
        ]);
        return $this->success(['pay_no'=>'PAY'.date('YmdHis')],'打款成功');
    }

    public function withdrawRetry(Request $request,$id) {
        $w=DB::table('withdraws')->where('id',$id)->first();
        if(!$w)return $this->error('提现记录不存在');
        if($w->status!=1)return $this->error('只有待打款状态的提现单可以重试');
        if($w->retry_count >= 3)return $this->error('该提现单已重试3次，请人工核查后处理');
        $adminId = $request->user()->id ?? 1;
        $isFail = $request->input('is_fail', 0);
        $failureReason = $request->input('failure_reason', '');
        if ($isFail) {
            DB::table('withdraws')->where('id',$id)->update([
                'failure_reason' => $failureReason ?: '重试打款失败',
                'retry_count' => DB::raw('retry_count + 1'),
                'admin_id' => $adminId,
                'updated_at' => now(),
            ]);
            return $this->error('重试打款失败：' . ($failureReason ?: '第三方支付接口调用失败'));
        }
        $payNo = 'PAY'.date('YmdHis').rand(1000,9999);
        DB::table('withdraws')->where('id',$id)->update([
            'status'=>3,
            'paid_at'=>now(),
            'pay_no'=>$payNo,
            'failure_reason'=>null,
            'admin_id'=>$adminId,
            'updated_at'=>now(),
        ]);
        return $this->success(['pay_no'=>$payNo,'retry_count'=>$w->retry_count + 1],'重试打款成功');
    }

    public function withdrawSettings(Request $request) {
        if ($request->isMethod('get')) {
            $configs = DB::table('system_configs')->where('group','withdraw')->where('status',1)->orderBy('sort','asc')->get();
            $result = [];
            foreach ($configs as $cfg) {
                $result[$cfg->key] = ['name'=>$cfg->name,'value'=>$cfg->value,'type'=>$cfg->type];
            }
            return $this->success($result);
        }
        if ($request->isMethod('put') || $request->isMethod('post')) {
            $updates = $request->only(['min_withdraw_amount','max_withdraw_amount','daily_withdraw_limit']);
            foreach ($updates as $key => $value) {
                if ($value !== null) {
                    DB::table('system_configs')->where('group','withdraw')->where('key',$key)->update(['value'=>$value,'updated_at'=>now()]);
                }
            }
            return $this->success(null,'提现限额配置已更新');
        }
        return $this->error('不支持的请求方法');
    }
    public function addresses(Request $request) {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $query = \App\Models\UserAddress::query()->with('user:id,mobile,nickname');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('keyword')) {
            $kw = $request->input('keyword');
            $query->where(function($q) use ($kw) {
                $q->where('name', 'like', "%{$kw}%")
                  ->orWhere('mobile', 'like', "%{$kw}%")
                  ->orWhere('detail', 'like', "%{$kw}%");
            });
        }
        $total = $query->count();
        $list = $query->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    public function accountLogs(Request $request) {
        $q=DB::table('user_balance_logs as l')->leftJoin('users as u','l.user_id','=','u.id')->select('l.*','u.nickname','u.mobile');
        if($request->filled('user_id'))$q->where('l.user_id',$request->user_id);
        if($request->filled('type'))$q->where('l.type',$request->type);
        if($request->filled('keyword'))$q->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('l.order_no','like','%'.$request->keyword.'%')->orWhere('l.remark','like','%'.$request->keyword.'%');});
        $total=$q->count();$page=$request->input('page',1);$limit=$request->input('limit',20);
        $list=$q->orderBy('l.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats=DB::table('user_balance_logs')->select('type',DB::raw('SUM(amount) as total_amount,COUNT(*) as count'))->groupBy('type')->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }
}