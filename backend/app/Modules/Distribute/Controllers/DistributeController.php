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
        $query = DB::table('distribute_agents');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('nickname','like','%'.$request->keyword.'%')->orWhere('mobile','like','%'.$request->keyword.'%');});
        if ($request->filled('level_id')) $query->where('level_id',$request->level_id);
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    public function agentAudit(Request $request, $id) {
        $validated = $request->validate(['status'=>'required|in:1,2']);
        DB::table('distribute_agents')->where('id',$id)->update(['status'=>$validated['status'],'updated_at'=>now()]);
        return $this->success(null,$validated['status']==1?'审核通过':'已拒绝');
    }

    public function levels() {
        $list = DB::table('distribute_levels')->orderBy('level','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }

    public function levelStore(Request $request) {
        $validated = $request->validate(['name'=>'required|string','level'=>'required|integer','commission_rate'=>'required|numeric','description'=>'nullable|string']);
        $validated['created_at']=now(); $validated['updated_at']=now();
        $id = DB::table('distribute_levels')->insertGetId($validated);
        return $this->success(['id'=>$id],'创建成功');
    }

    public function levelUpdate(Request $request, $id) {
        $validated = $request->validate(['name'=>'sometimes|required|string','commission_rate'=>'sometimes|required|numeric','description'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $validated['updated_at']=now();
        DB::table('distribute_levels')->where('id',$id)->update($validated);
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
