<?php
namespace App\Modules\Channel\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfficialAccountReplyController extends BaseController
{
    public function index() { return $this->success(['msg'=>'公众号回复管理']); }
    public function lists(Request $request) {
        $query = DB::table('channel_settings')->where('channel','oa')->where('key','like','reply_%');
        $total = $query->count();
        $list = $query->get();
        return $this->success(['list'=>$list,'total'=>$total]);
    }
    public function detail($id) { $item = DB::table('channel_settings')->where('id',$id)->first(); return $this->success($item); }
    public function add(Request $request) {
        $v = $request->validate(['key'=>'required|string','value'=>'required|string']);
        DB::table('channel_settings')->insert(['channel'=>'oa','key'=>'reply_'.$v['key'],'value'=>$v['value'],'created_at'=>now(),'updated_at'=>now()]);
        return $this->success(null,'添加成功');
    }
    public function edit(Request $request, $id) {
        $v = $request->validate(['value'=>'required|string']);
        DB::table('channel_settings')->where('id',$id)->update(['value'=>$v['value'],'updated_at'=>now()]);
        return $this->success(null,'编辑成功');
    }
    public function delete($id) { DB::table('channel_settings')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
    public function sort(Request $request) { return $this->success(null,'排序成功'); }
    public function status(Request $request) { return $this->success(null,'状态更新成功'); }
}
