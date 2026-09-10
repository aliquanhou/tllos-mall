<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BargainController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('bargains as b')->leftJoin('products as p','b.goods_id','=','p.id');
        if ($request->keyword) $query->where('b.name','like','%'.$request->keyword.'%');
        if ($request->status !== null) $query->where('b.status', $request->status);
        $list = $query->select('b.*','p.name as goods_name','p.main_image')->orderBy('b.id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','goods_id','original_price','min_price','bargain_min','bargain_max','start_time','end_time','total_count','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('bargains')->insertGetId($data);
        return $this->success(DB::table('bargains')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','goods_id','original_price','min_price','bargain_min','bargain_max','start_time','end_time','total_count','status']);
        $data['updated_at'] = now();
        DB::table('bargains')->where('id',$id)->update($data);
        return $this->success(DB::table('bargains')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('bargains')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
    public function records(Request $request) {
        $query = DB::table('bargain_records as r')->leftJoin('bargains as b','r.bargain_id','=','b.id')->leftJoin('users as u','r.user_id','=','u.id');
        $list = $query->select('r.*','b.name as bargain_name','u.nickname')->orderBy('r.id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
}
