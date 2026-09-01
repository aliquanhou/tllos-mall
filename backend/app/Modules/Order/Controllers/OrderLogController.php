<?php
namespace App\Modules\Order\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderLogController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('order_logs');
        if($request->filled('order_id')) $query->where('order_id',$request->order_id);
        if($request->filled('order_no')) $query->where('order_no','like','%'.$request->order_no.'%');
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
