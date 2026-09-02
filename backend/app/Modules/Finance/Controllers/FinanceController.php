<?php
namespace App\Modules\Finance\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends BaseController
{
    public function income(Request $request) {
        $query = DB::table('orders as o')->leftJoin('users as u','o.user_id','=','u.id')->leftJoin('merchants as m','o.merchant_id','=','m.id')->select('o.id','o.order_no','o.pay_amount','o.pay_type','o.pay_time','o.status','u.nickname','u.mobile','m.name as merchant_name');
        $query->where('o.status','>=',1)->where('o.status','!=',4)->whereNotNull('o.pay_time');
        if ($request->filled('keyword')) $query->where(function($q) use ($request) { $q->where('o.order_no','like','%'.$request->keyword.'%')->orWhere('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%'); });
        if ($request->filled('pay_type')) $query->where('o.pay_type',$request->pay_type);
        $total = $query->count(); $totalAmount = $query->sum('o.pay_amount');
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = $query->orderBy('o.pay_time','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = ['total_count'=>$total,'total_amount'=>round($totalAmount,2),'today_count'=>DB::table('orders')->where('status','>=',1)->where('status','!=',4)->whereDate('pay_time',today())->count(),'today_amount'=>round(DB::table('orders')->where('status','>=',1)->where('status','!=',4)->whereDate('pay_time',today())->sum('pay_amount'),2)];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function refund(Request $request) {
        $query = DB::table('refunds as r')->leftJoin('orders as o','r.order_id','=','o.id')->leftJoin('users as u','r.user_id','=','u.id')->select('r.*','o.order_no','o.pay_amount as order_amount','u.nickname','u.mobile');
        if ($request->filled('keyword')) $query->where(function($q) use ($request) { $q->where('o.order_no','like','%'.$request->keyword.'%')->orWhere('u.nickname','like','%'.$request->keyword.'%')->orWhere('r.refund_no','like','%'.$request->keyword.'%'); });
        if ($request->filled('status') && $request->status !== '') $query->where('r.status',$request->status);
        $total = $query->count(); $totalAmount = $query->sum('r.refund_amount');
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = $query->orderBy('r.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = ['total_count'=>$total,'total_amount'=>round($totalAmount,2),'pending_count'=>DB::table('refunds')->where('status',0)->count(),'approved_count'=>DB::table('refunds')->where('status',1)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function withdraw(Request $request) {
        $query = DB::table('merchant_withdraws');
        if ($request->filled('keyword')) $query->where(function($q) use ($request) { $q->where('merchant_name','like','%'.$request->keyword.'%')->orWhere('bank_holder','like','%'.$request->keyword.'%')->orWhere('bank_account','like','%'.$request->keyword.'%'); });
        if ($request->filled('status') && $request->status !== '') $query->where('status',$request->status);
        $total = $query->count(); $totalAmount = $query->sum('amount');
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = ['total_count'=>$total,'total_amount'=>round($totalAmount,2),'pending_count'=>DB::table('merchant_withdraws')->where('status',0)->count(),'pending_amount'=>round(DB::table('merchant_withdraws')->where('status',0)->sum('amount'),2),'paid_count'=>DB::table('merchant_withdraws')->where('status',3)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function withdrawAudit(Request $request, $id) {
        $validated = $request->validate(['status'=>'required|in:1,2','reject_reason'=>'nullable|string|max:255']);
        $w = DB::table('merchant_withdraws')->where('id',$id)->first();
        if (!$w) return $this->error('提现记录不存在');
        if ($w->status != 0) return $this->error('该提现已审核');
        $update = ['status'=>$validated['status'],'audit_time'=>now(),'updated_at'=>now()];
        if ($validated['status']==2 && !empty($validated['reject_reason'])) $update['reject_reason']=$validated['reject_reason'];
        DB::table('merchant_withdraws')->where('id',$id)->update($update);
        return $this->success(null,$validated['status']==1?'审核通过':'已拒绝');
    }

    public function withdrawPay($id) {
        $w = DB::table('merchant_withdraws')->where('id',$id)->first();
        if (!$w) return $this->error('提现记录不存在');
        if ($w->status != 1) return $this->error('只有审核通过的提现才能打款');
        DB::table('merchant_withdraws')->where('id',$id)->update(['status'=>3,'pay_time'=>now(),'pay_no'=>'PAY'.date('YmdHis').rand(1000,9999),'updated_at'=>now()]);
        return $this->success(null,'打款成功');
    }

    public function settlement(Request $request) {
        $query = DB::table('merchant_settlements as s')->leftJoin('merchants as m','s.merchant_id','=','m.id')->select('s.*','m.name as merchant_name');
        if ($request->filled('keyword')) $query->where(function($q) use ($request) { $q->where('s.settlement_no','like','%'.$request->keyword.'%')->orWhere('m.name','like','%'.$request->keyword.'%'); });
        if ($request->filled('status') && $request->status !== '') $query->where('s.status',$request->status);
        $total = $query->count(); $totalAmount = $query->sum('s.settlement_amount');
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = $query->orderBy('s.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = ['total_count'=>$total,'total_amount'=>round($totalAmount,2),'pending_count'=>DB::table('merchant_settlements')->where('status',0)->count(),'pending_amount'=>round(DB::table('merchant_settlements')->where('status',0)->sum('settlement_amount'),2),'settled_count'=>DB::table('merchant_settlements')->where('status',1)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function settlementConfirm($id) {
        $s = DB::table('merchant_settlements')->where('id',$id)->first();
        if (!$s) return $this->error('结算单不存在');
        if ($s->status != 0) return $this->error('该结算单已处理');
        DB::beginTransaction();
        try {
            DB::table('merchant_settlements')->where('id',$id)->update(['status'=>1,'settled_at'=>now(),'updated_at'=>now()]);
            // 更新商家余额
            $merchant = DB::table('merchants')->where('id', $s->merchant_id)->first();
            if ($merchant) {
                $balanceBefore = $merchant->balance;
                DB::table('merchants')->where('id', $s->merchant_id)->increment('balance', $s->settlement_amount);
                // 记录商家账户日志
                DB::table('merchant_account_logs')->insert([
                    'merchant_id' => $s->merchant_id,
                    'type' => 1,
                    'amount' => $s->settlement_amount,
                    'before_balance' => $balanceBefore,
                    'after_balance' => $balanceBefore + $s->settlement_amount,
                    'remark' => '结算单' . $s->settlement_no . '结算到账',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return $this->success(null,'结算成功，金额已到账商家余额');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('结算失败: ' . $e->getMessage());
        }
    }
}
