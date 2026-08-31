<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemConfigController extends BaseController
{
    public function dictTypes() {
        $list = DB::table('dict_types')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function dictTypeStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','code'=>'required|string','remark'=>'nullable|string','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('dict_types')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function dictTypeUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','remark'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('dict_types')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function dictTypeDestroy($id) {
        DB::table('dict_types')->where('id',$id)->delete();
        DB::table('dict_datas')->where('type_id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function dictDatas(Request $request) {
        $query = DB::table('dict_datas');
        if ($request->filled('type_id')) $query->where('type_id',$request->type_id);
        $list = $query->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function dictDataStore(Request $request) {
        $v = $request->validate(['type_id'=>'required|integer','label'=>'required|string','value'=>'required|string','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('dict_datas')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function dictDataUpdate(Request $request, $id) {
        $v = $request->validate(['label'=>'sometimes|required|string','value'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('dict_datas')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function dictDataDestroy($id) {
        DB::table('dict_datas')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function hotSearches() {
        $list = DB::table('hot_searches')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function hotSearchStore(Request $request) {
        $v = $request->validate(['keyword'=>'required|string','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('hot_searches')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function hotSearchUpdate(Request $request, $id) {
        $v = $request->validate(['keyword'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('hot_searches')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function hotSearchDestroy($id) {
        DB::table('hot_searches')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function crontabs() {
        $list = DB::table('crontabs')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function crontabToggle($id) {
        $item = DB::table('crontabs')->where('id',$id)->first();
        $new = $item->status==1?0:1;
        DB::table('crontabs')->where('id',$id)->update(['status'=>$new,'updated_at'=>now()]);
        return $this->success(['status'=>$new],$new==1?'已启用':'已禁用');
    }
    public function areas(Request $request) {
        $query = DB::table('areas');
        if ($request->filled('parent_id')) $query->where('parent_id',$request->parent_id);
        if ($request->filled('level')) $query->where('level',$request->level);
        $list = $query->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function expressTemplates() {
        $list = DB::table('express_templates')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function expressTemplateStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','type'=>'required|integer','first_fee'=>'nullable|numeric','continue_fee'=>'nullable|numeric','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('express_templates')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function expressTemplateUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','first_fee'=>'sometimes|numeric','continue_fee'=>'sometimes|numeric','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('express_templates')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function expressTemplateDestroy($id) {
        DB::table('express_templates')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function files(Request $request) {
        $query = DB::table('files');
        if ($request->filled('type')) $query->where('type',$request->type);
        if ($request->filled('category_id')) $query->where('category_id',$request->category_id);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function fileCategories() {
        $list = DB::table('file_categories')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
}
