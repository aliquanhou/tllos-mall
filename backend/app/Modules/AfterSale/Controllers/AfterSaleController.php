<?php
namespace App\Modules\AfterSale\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AfterSaleController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('order_after_sales');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('order_no','like','%'.$request->keyword.'%')->orWhere('user_name','like','%'.$request->keyword.'%');});
        if ($request->filled('type')) $query->where('type',$request->type);
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function show($id) {
        $item = DB::table('order_after_sales')->where('id',$id)->first();
        if (!$item) return $this->error('售后单不存在');
        $logs = DB::table('order_after_sale_logs')->where('after_sale_id',$id)->orderBy('id','asc')->get();
        return $this->success(['info'=>$item,'logs'=>$logs]);
    }
    public function audit(Request $request, $id) {
        $v = $request->validate(['status'=>'required|in:1,2','refuse_reason'=>'nullable|string']);
        DB::table('order_after_sales')->where('id',$id)->update(['status'=>$v['status'],'refuse_reason'=>$v['refuse_reason']??null,'audit_time'=>now(),'updated_at'=>now()]);
        DB::table('order_after_sale_logs')->insert(['after_sale_id'=>$id,'action'=>$v['status']==1?'审核通过':'审核拒绝','operator'=>'admin','remark'=>$v['refuse_reason']??'','created_at'=>now()]);
        return $this->success(null,$v['status']==1?'审核通过':'已拒绝');
    }
    public function complete($id) {
        DB::table('order_after_sales')->where('id',$id)->update(['status'=>3,'updated_at'=>now()]);
        DB::table('order_after_sale_logs')->insert(['after_sale_id'=>$id,'action'=>'售后完成','operator'=>'admin','created_at'=>now()]);
        return $this->success(null,'已完成');
    }
}
