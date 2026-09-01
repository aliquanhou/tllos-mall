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
            'total_orders' => DB::table('distribute_orders')->count(),
            'total_commission' => round(DB::table('distribute_orders')->sum('commission'),2),
            'total_goods' => DB::table('distribute_goods')->where('status',1)->count(),
        ];
        $recentOrders = DB::table('distribute_orders')->orderBy('id','desc')->limit(10)->get();
        $topAgents = DB::table('distribute_agents')->orderBy('total_income','desc')->limit(5)->get();
        return $this->success(['stats'=>$stats,'recent_orders'=>$recentOrders,'top_agents'=>$topAgents]);
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
        $query = DB::table('distribute_orders');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('order_no','like','%'.$request->keyword.'%')->orWhere('agent_name','like','%'.$request->keyword.'%');});
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    public function goods(Request $request) {
        $query = DB::table('distribute_goods');
        if ($request->filled('keyword')) $query->where('product_name','like','%'.$request->keyword.'%');
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
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
