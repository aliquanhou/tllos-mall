<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PtOpenController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('pt_opens');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('user_name','like','%'.$request->keyword.'%')->orWhere('order_no','like','%'.$request->keyword.'%');});
        if ($request->filled('status')&&$request->status!=='') $query->where('status',$request->status);
        $total=$query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list=$query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
