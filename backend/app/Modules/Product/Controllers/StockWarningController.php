<?php
namespace App\Modules\Product\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StockWarningController extends BaseController {
    public function index(Request $request) {
        try {
            $list = DB::table('products as p')
                ->select('p.id','p.name','p.main_image','p.stock','p.warning_stock')
                ->whereColumn('p.stock','<=','p.warning_stock')
                ->orderBy('p.stock','asc')
                ->paginate($request->get('limit',20));
            return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function setting(Request $request) {
        $request->validate(['goods_id'=>'required|integer','warning_stock'=>'required|integer|min:1']);
        DB::table('products')->where('id',$request->goods_id)->update(['warning_stock'=>$request->warning_stock]);
        return $this->success(null,'设置成功');
    }
}
