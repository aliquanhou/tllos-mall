<?php
namespace App\Modules\Finance\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementRecordController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('settlement_records');
        if ($request->filled('settlement_id')) $query->where('settlement_id',$request->settlement_id);
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('shop_name','like','%'.$request->keyword.'%')->orWhere('order_no','like','%'.$request->keyword.'%');});
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
