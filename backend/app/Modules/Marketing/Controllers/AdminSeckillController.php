<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminSeckillController extends BaseController {
    public function index(Request $request) {
        try {
            $query = DB::table('seckills');
            if ($request->keyword) $query->where('name','like','%'.$request->keyword.'%');
            $list = $query->orderBy('id','desc')->paginate($request->get('limit',20));
            return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function show($id) {
        $item = DB::table('seckills')->where('id',$id)->first();
        return $this->success($item);
    }
    public function store(Request $request) {
        $data = $request->only(['name','start_time','end_time','status']);
        $data['created_at'] = now();
        $id = DB::table('seckills')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','start_time','end_time','status']);
        DB::table('seckills')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('seckills')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function goods($id) {
        $list = DB::table('seckill_goods')->where('seckill_id',$id)->get();
        return $this->success($list);
    }
}
