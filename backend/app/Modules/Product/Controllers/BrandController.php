<?php
namespace App\Modules\Product\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BrandController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('brands');
        if ($request->keyword) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->status !== null) $query->where('status', $request->status);
        $list = $query->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function all() { return $this->success(DB::table('brands')->where('status',1)->orderBy('sort','asc')->get()); }
    public function store(Request $request) {
        $data = $request->only(['name','logo','description','sort','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('brands')->insertGetId($data);
        return $this->success(DB::table('brands')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','logo','description','sort','status']);
        $data['updated_at'] = now();
        DB::table('brands')->where('id',$id)->update($data);
        return $this->success(DB::table('brands')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('brands')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
