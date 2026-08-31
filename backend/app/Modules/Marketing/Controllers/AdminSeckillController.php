<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminSeckillController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('seckills');
        if ($request->keyword) $query->where('name', 'like', '%'.$request->keyword.'%');
        if ($request->status !== null) $query->where('status', $request->status);
        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?? 20);
        return $this->success(['list' => $list->items(), 'total' => $list->total(), 'page' => $list->currentPage(), 'limit' => $list->perPage()]);
    }
    public function show($id)
    {
        $item = DB::table('seckills')->where('id', $id)->first();
        if (!$item) return $this->error('数据不存在');
        return $this->success($item);
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('seckills')->insertGetId($data);
        return $this->success(DB::table('seckills')->where('id', $id)->first(), '创建成功');
    }
    public function update(Request $request, $id)
    {
        $item = DB::table('seckills')->where('id', $id)->first();
        if (!$item) return $this->error('数据不存在');
        $data = $request->all();
        $data['updated_at'] = now();
        DB::table('seckills')->where('id', $id)->update($data);
        return $this->success(DB::table('seckills')->where('id', $id)->first(), '更新成功');
    }
    public function destroy($id)
    {
        $item = DB::table('seckills')->where('id', $id)->first();
        if (!$item) return $this->error('数据不存在');
        DB::table('seckills')->where('id', $id)->delete();
        return $this->success(null, '删除成功');
    }
}
