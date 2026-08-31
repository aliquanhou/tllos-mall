<?php
namespace App\Modules\Application\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends BaseController
{
    public function depositList(Request $request) {
        $query = DB::table('user_recharges as ub')->join('users as u','ub.user_id','=','u.id')->select('ub.*','u.nickname','u.mobile');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('u.nickname','like','%'.$request->keyword.'%')->orWhere('u.mobile','like','%'.$request->keyword.'%');});
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('ub.id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    public function materialList(Request $request) {
        $query = DB::table('materials');
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        if ($request->filled('type')) $query->where('type',$request->type);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }

    public function articleList(Request $request) {
        $query = DB::table('articles');
        if ($request->filled('keyword')) $query->where('title','like','%'.$request->keyword.'%');
        if ($request->filled('category_id')) $query->where('category_id',$request->category_id);
        if ($request->filled('status') && $request->status!=='') $query->where('status',$request->status);
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function articleStore(Request $request) {
        $v = $request->validate(['title'=>'required|string','category_id'=>'required|integer','content'=>'nullable|string','summary'=>'nullable|string','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $cat = DB::table('article_categories')->where('id',$v['category_id'])->first();
        $v['category_name'] = $cat?$cat->name:'';
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('articles')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function articleUpdate(Request $request, $id) {
        $v = $request->validate(['title'=>'sometimes|required|string','category_id'=>'sometimes|required|integer','content'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        if (isset($v['category_id'])) { $cat = DB::table('article_categories')->where('id',$v['category_id'])->first(); $v['category_name']=$cat?$cat->name:''; }
        $v['updated_at']=now();
        DB::table('articles')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function articleDestroy($id) { DB::table('articles')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
    public function articleCategories() { $list = DB::table('article_categories')->orderBy('sort','asc')->get(); return $this->success(['list'=>$list,'total'=>count($list)]); }

    public function noticeList(Request $request) {
        $query = DB::table('notices');
        if ($request->filled('keyword')) $query->where('title','like','%'.$request->keyword.'%');
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('sort','asc')->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function noticeStore(Request $request) {
        $v = $request->validate(['title'=>'required|string','content'=>'nullable|string','type'=>'nullable|integer','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('notices')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function noticeUpdate(Request $request, $id) {
        $v = $request->validate(['title'=>'sometimes|required|string','content'=>'sometimes|nullable|string','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('notices')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function noticeDestroy($id) { DB::table('notices')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }

    public function kefuSetting() {
        $settings = DB::table('system_configs')->whereIn('key',['kefu_enabled','kefu_phone','kefu_wechat','kefu_worktime','kefu_qq'])->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function kefuSave(Request $request) {
        $data = $request->all();
        foreach ($data as $key=>$value) {
            if (is_string($key) && !empty($key)) {
                DB::table('system_configs')->updateOrInsert(['key'=>$key],['value'=>$value,'updated_at'=>now()]);
            }
        }
        return $this->success(null,'保存成功');
    }

    public function collectList(Request $request) {
        $query = DB::table('products')->where('source','!=','manual')->orWhereNotNull('source_url');
        if ($request->filled('keyword')) $query->where('name','like','%'.$request->keyword.'%');
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('id','desc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
}
