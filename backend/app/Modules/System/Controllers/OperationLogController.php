<?php
namespace App\Modules\System\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OperationLogController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('operation_logs');
        if ($request->admin_name) $query->where('admin_name', 'like', '%'.$request->admin_name.'%');
        $total = $query->count();
        $list = $query->orderBy('id', 'desc')->offset(($request->page-1)*($request->limit?:20))->limit($request->limit?:20)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$request->page?:1,'limit'=>$request->limit?:20]);
    }
}
