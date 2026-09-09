<?php
namespace App\Modules\System\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDictController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('dicts');
        if ($request->keyword) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->type) $query->where('type', $request->type);
        $total = $query->count();
        $list = $query->orderBy('id', 'desc')->offset(($request->page-1)*($request->limit?:20))->limit($request->limit?:20)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$request->page?:1,'limit'=>$request->limit?:20]);
    }
    public function show($id) {
        $item = DB::table('dicts')->where('id', $id)->first();
        if (!$item) return $this->error('数据不存在');
        return $this->success($item);
    }
    public function store(Request $request) {
        $data = $request->only(['type','name','label','value','sort','status']);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('dicts')->insertGetId($data);
        return $this->success(DB::table('dicts')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['type','name','label','value','sort','status']);
        $data['updated_at'] = now();
        DB::table('dicts')->where('id',$id)->update($data);
        return $this->success(DB::table('dicts')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) {
        DB::table('dicts')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
