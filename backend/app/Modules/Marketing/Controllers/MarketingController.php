<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingController extends BaseController
{
    public function seckillList(Request $request) {
        $query = DB::table('seckill_activities');
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        foreach ($list as &$item) { $item->goods_count = DB::table('seckill_goods')->where('activity_id',$item->id)->count(); }
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function seckillStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','start_time'=>'required|date','end_time'=>'required|date','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('seckill_activities')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function seckillUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','start_time'=>'sometimes|required|date','end_time'=>'sometimes|required|date','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('seckill_activities')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function seckillDestroy($id) {
        DB::table('seckill_activities')->where('id',$id)->delete();
        DB::table('seckill_goods')->where('activity_id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function seckillGoods($id) {
        $list = DB::table('seckill_goods')->where('activity_id',$id)->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }

    public function groupList(Request $request) {
        $query = DB::table('group_activities');
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function groupStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','product_id'=>'required|integer','group_price'=>'required|numeric','original_price'=>'required|numeric','group_num'=>'required|integer','stock'=>'required|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('group_activities')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function groupUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','group_price'=>'sometimes|required|numeric','group_num'=>'sometimes|integer','stock'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('group_activities')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function groupDestroy($id) {
        DB::table('group_activities')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }

    public function discountList() {
        $list = DB::table('member_discounts')->orderBy('level','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function discountStore(Request $request) {
        $v = $request->validate(['level_name'=>'required|string','level'=>'required|integer','discount_rate'=>'required|numeric','description'=>'nullable|string','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('member_discounts')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function discountUpdate(Request $request, $id) {
        $v = $request->validate(['level_name'=>'sometimes|required|string','discount_rate'=>'sometimes|required|numeric','description'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('member_discounts')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function discountDestroy($id) {
        DB::table('member_discounts')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
