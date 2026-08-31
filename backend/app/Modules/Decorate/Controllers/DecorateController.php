<?php
namespace App\Modules\Decorate\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DecorateController extends BaseController
{
    public function pages() {
        $list = DB::table('decorate_pages')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function pageSave(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','content'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('decorate_pages')->where('id',$id)->update($v);
        return $this->success(null,'保存成功');
    }
    public function tabbars() {
        $list = DB::table('decorate_tabbars')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function tabbarStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','icon'=>'nullable|string','link_url'=>'nullable|string','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('decorate_tabbars')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function tabbarUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('decorate_tabbars')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function tabbarDestroy($id) {
        DB::table('decorate_tabbars')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function categoryAds() {
        $list = DB::table('decorate_category_ads')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
}
