<?php
namespace App\Modules\Announcement\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('announcements');
        if ($request->filled('keyword')) $query->where('title','like','%'.$request->keyword.'%');
        if ($request->filled('type')) $query->where('type',$request->type);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function store(Request $request) {
        $v = $request->validate(['title'=>'required|string','content'=>'nullable|string','type'=>'nullable|integer','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('announcements')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $v = $request->validate(['title'=>'sometimes|required|string','content'=>'sometimes|nullable|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('announcements')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        DB::table('announcements')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
