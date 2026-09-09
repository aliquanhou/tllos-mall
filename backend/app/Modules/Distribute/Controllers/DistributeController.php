<?php
namespace App\Modules\Distribute\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributeController extends BaseController
{
    public function overview() {
        $stats = [
            'total_agents' => DB::table('distribute_agents')->count(),
            'active_agents' => DB::table('distribute_agents')->where('status',1)->count(),
            'pending_agents' => DB::table('distribute_agents')->where('status',0)->count(),
            'today_new_agents' => DB::table('distribute_agents')->whereDate('created_at',date('Y-m-d'))->count(),
            'total_orders' => DB::table('distribute_orders')->count(),
            'week_orders' => DB::table('distribute_orders')->where('created_at','>=',date('Y-m-d',strtotime('-7 days')))->count(),
            'total_commission' => round(DB::table('distribute_orders')->sum('commission'),2),
            'settled_commission' => round(DB::table('distribute_orders')->where('status',1)->sum('commission'),2),
            'pending_commission' => round(DB::table('distribute_orders')->where('status',0)->sum('commission'),2),
            'total_goods' => DB::table('distribute_goods')->where('status',1)->count(),
        ];
        $trend = [];
        for ($i=6; $i>=0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trend['labels'][] = date('m-d', strtotime($date));
            $trend['orders'][] = DB::table('distribute_orders')->whereDate('created_at',$date)->count();
            $trend['commission'][] = round(DB::table('distribute_orders')->whereDate('created_at',$date)->sum('commission'),2);
        }
        $recentOrders = DB::table('distribute_orders as do')
            ->leftJoin('users as u','do.user_id','=','u.id')
            ->leftJoin('distribute_agents as da','do.agent_id','=','da.id')
            ->select('do.*','u.nickname as user_name','da.real_name as agent_name')
            ->orderBy('do.id','desc')->limit(10)->get();
        $topAgents = DB::table('distribute_agents as da')
            ->leftJoin('users as u','da.user_id','=','u.id')
            ->select('da.*','u.nickname','u.mobile')
            ->orderBy('da.total_income','desc')->limit(5)->get();
        $topGoods = DB::table('distribute_goods as dg')
            ->leftJoin('products as p','dg.product_id','=','p.id')
            ->select('dg.*','p.sales')
            ->where('dg.status',1)
            ->orderBy('p.sales','desc')->limit(5)->get();
        return $this->success(['stats'=>$stats,'trend'=>$trend,'recent_orders'=>$recentOrders,'top_agents'=>$topAgents,'top_goods'=>$topGoods]);
    }

    public function agents(Request $request) {
        $query = DB::table('distribute_agents as da')
            ->leftJoin('users as u','da.user_id','=','u.id')
            ->leftJoin('distribute_levels as dl','da.level_id','=','dl.id')
            ->leftJoin('distribute_agents as parent','da.parent_id','=','parent.id');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('da.mobile','like','%'.$request->keyword.'%')->orWhere('da.real_name','like','%'.$request->keyword.'%');});
        if ($request->filled('level_id')) $query->where('da.level_id',$request->level_id);
        if ($request->filled('status') && $request->status!=='') $query->where('da.status',$request->status);
        if ($request->filled('parent_id')) $query->where('da.parent_id',$request->parent_id);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->select('da.*','u.nickname','u.avatar','dl.name as level_name','parent.real_name as parent_name')
            ->orderBy('da.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = [
            'total' => DB::table('distribute_agents')->count(),
            'pending' => DB::table('distribute_agents')->where('status',0)->count(),
            'approved' => DB::table('distribute_agents')->where('status',1)->count(),
            'rejected' => DB::table('distribute_agents')->where('status',2)->count(),
            'disabled' => DB::table('distribute_agents')->where('status',3)->count(),
        ];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function agentAudit(Request $request, $id) {
        $v = $request->validate(['status'=>'required|in:1,2','remark'=>'nullable|string|max:255']);
        $agent = DB::table('distribute_agents')->where('id',$id)->first();
        if (!$agent) return $this->error('分销商不存在');
        if ($agent->status != 0) return $this->error('该分销商已审核，不能重复审核');
        $update = ['status'=>$v['status'],'audit_at'=>now(),'updated_at'=>now()];
        DB::table('distribute_agents')->where('id',$id)->update($update);
        // 发送审核通知
        if ($v['status']==1) {
            $title = '分销商审核通过通知';
            $content = '恭喜您！您的分销商申请已审核通过，您现在可以推广商品并获得佣金收益。';
        } else {
            $title = '分销商审核拒绝通知';
            $content = '很抱歉，您的分销商申请未通过审核。原因：'.($v['remark'] ?? '资料不符合要求').'。如有疑问请联系客服。';
        }
        DB::table('user_notifications')->insert([
            'user_id'=>$agent->user_id,
            'title'=>$title,
            'content'=>$content,
            'type'=>'distribute',
            'is_read'=>0,
            'created_at'=>now(),
            'updated_at'=>now()
        ]);
        return $this->success(null,$v['status']==1?'审核通过':'已拒绝');
    }

    public function levels(Request $request) {
        $query = DB::table('distribute_levels');
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        $total = $query->count();
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = $query->orderBy('level','asc')->offset(($page-1)*$limit)->limit($limit)->get();
        // 统计每个等级的分销商数量
        foreach ($list as $level) {
            $level->agent_count = DB::table('distribute_agents')->where('level_id',$level->id)->count();
        }
        $stats = ['total'=>$total,'active'=>DB::table('distribute_levels')->where('status',1)->count(),'inactive'=>DB::table('distribute_levels')->where('status',0)->count()];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function levelStore(Request $request) {
        $v = $request->validate([
            'name'=>'required|string|max:50',
            'level'=>'required|integer|min:1',
            'commission_rate'=>'required|numeric|min:0|max:100',
            'self_rate'=>'nullable|numeric|min:0|max:100',
            'first_rate'=>'nullable|numeric|min:0|max:100',
            'second_rate'=>'nullable|numeric|min:0|max:100',
            'third_rate'=>'nullable|numeric|min:0|max:100',
            'upgrade_orders'=>'nullable|integer|min:0',
            'upgrade_amount'=>'nullable|numeric|min:0',
            'sort'=>'nullable|integer',
            'status'=>'nullable|integer|in:0,1'
        ]);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('distribute_levels')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }

    public function levelUpdate(Request $request, $id) {
        $v = $request->validate([
            'name'=>'sometimes|required|string|max:50',
            'level'=>'sometimes|required|integer|min:1',
            'commission_rate'=>'sometimes|required|numeric|min:0|max:100',
            'self_rate'=>'sometimes|nullable|numeric|min:0|max:100',
            'first_rate'=>'sometimes|nullable|numeric|min:0|max:100',
            'second_rate'=>'sometimes|nullable|numeric|min:0|max:100',
            'third_rate'=>'sometimes|nullable|numeric|min:0|max:100',
            'upgrade_orders'=>'sometimes|nullable|integer|min:0',
            'upgrade_amount'=>'sometimes|nullable|numeric|min:0',
            'sort'=>'sometimes|nullable|integer',
            'status'=>'sometimes|integer|in:0,1'
        ]);
        $v['updated_at']=now();
        DB::table('distribute_levels')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }

    public function orders(Request $request) {
        $query = DB::table('distribute_orders as do')
            ->leftJoin('users as u','do.user_id','=','u.id')
            ->leftJoin('distribute_agents as da','do.agent_id','=','da.id')
            ->leftJoin('distribute_levels as dl','do.level_id','=','dl.id');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('do.order_no','like','%'.$request->keyword.'%')->orWhere('da.real_name','like','%'.$request->keyword.'%')->orWhere('u.nickname','like','%'.$request->keyword.'%');});
        if ($request->filled('status') && $request->status!=='') $query->where('do.status',$request->status);
        if ($request->filled('agent_id')) $query->where('do.agent_id',$request->agent_id);
        if ($request->filled('start_time')) $query->where('do.created_at','>=',$request->start_time);
        if ($request->filled('end_time')) $query->where('do.created_at','<=',$request->end_time);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->select('do.*','u.nickname as user_name','u.mobile as user_mobile','da.real_name as agent_name','dl.name as level_name')
            ->orderBy('do.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = [
            'total' => DB::table('distribute_orders')->count(),
            'pending' => DB::table('distribute_orders')->where('status',0)->count(),
            'settled' => DB::table('distribute_orders')->where('status',1)->count(),
            'commission_total' => round(DB::table('distribute_orders')->sum('commission'),2),
            'commission_pending' => round(DB::table('distribute_orders')->where('status',0)->sum('commission'),2),
            'commission_settled' => round(DB::table('distribute_orders')->where('status',1)->sum('commission'),2),
        ];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function goods(Request $request) {
        $query = DB::table('distribute_goods as dg')->leftJoin('products as p','dg.product_id','=','p.id');
        if ($request->filled('keyword')) $query->where('dg.product_name','like','%'.$request->keyword.'%');
        if ($request->filled('status') && $request->status!=='') $query->where('dg.status',$request->status);
        if ($request->filled('commission_type')) $query->where('dg.commission_type',$request->commission_type);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->select('dg.*','p.sales','p.price','p.stock','p.status as product_status')
            ->orderBy('dg.sort','asc')->orderBy('dg.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        $stats = [
            'total' => DB::table('distribute_goods')->count(),
            'active' => DB::table('distribute_goods')->where('status',1)->count(),
            'inactive' => DB::table('distribute_goods')->where('status',0)->count(),
            'rate_type' => DB::table('distribute_goods')->where('commission_type',1)->count(),
            'amount_type' => DB::table('distribute_goods')->where('commission_type',2)->count(),
        ];
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit,'stats'=>$stats]);
    }

    public function goodsToggle($id) {
        $g = DB::table('distribute_goods')->where('id',$id)->first();
        if (!$g) return $this->error('商品不存在');
        $new = $g->status==1?0:1;
        DB::table('distribute_goods')->where('id',$id)->update(['status'=>$new,'updated_at'=>now()]);
        return $this->success(['status'=>$new],$new==1?'已开启':'已关闭');
    }
    public function goodsBatchToggle(Request $request) {
        $v = $request->validate(['ids'=>'required|array','ids.*'=>'integer','status'=>'required|integer|in:0,1']);
        $count = DB::table('distribute_goods')->whereIn('id',$v['ids'])->update(['status'=>$v['status'],'updated_at'=>now()]);
        return $this->success(['updated'=>$count],$v['status']==1?'批量开启成功':'批量关闭成功');
    }
    public function goodsBatchCommission(Request $request) {
        $v = $request->validate(['ids'=>'required|array','ids.*'=>'integer','commission_type'=>'required|integer|in:1,2','commission_rate'=>'nullable|numeric|min:0|max:100','commission_amount'=>'nullable|numeric|min:0']);
        $update = ['commission_type'=>$v['commission_type'],'updated_at'=>now()];
        if ($v['commission_type']==1) {
            if (!isset($v['commission_rate'])) return $this->error('按比例佣金时commission_rate必填');
            $update['commission_rate'] = $v['commission_rate'];
        } else {
            if (!isset($v['commission_amount'])) return $this->error('固定金额佣金时commission_amount必填');
            $update['commission_amount'] = $v['commission_amount'];
        }
        $count = DB::table('distribute_goods')->whereIn('id',$v['ids'])->update($update);
        return $this->success(['updated'=>$count],'批量设置佣金成功');
    }

    public function getSettings() {
        $settings = DB::table('distribute_settings')->pluck('value','key')->toArray();
        return $this->success($settings);
    }

    public function saveSettings(Request $request) {
        $data = $request->all();
        foreach ($data as $key=>$value) {
            if (is_string($key) && !empty($key)) {
                DB::table('distribute_settings')->updateOrInsert(['key'=>$key],['value'=>is_array($value)?json_encode($value):$value,'updated_at'=>now()]);
            }
        }
        return $this->success(null,'保存成功');
    }
}
