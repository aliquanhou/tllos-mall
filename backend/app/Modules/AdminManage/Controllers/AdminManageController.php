<?php
namespace App\Modules\AdminManage\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminManageController extends BaseController
{
    public function index(Request $request) {
        $query = DB::table('admins as a')->leftJoin('admin_roles as r','a.role_id','=','r.id')->select('a.id','a.username','a.nickname','a.avatar','a.mobile','a.email','a.role_id','a.status','a.last_login_at','a.last_login_ip','a.created_at','a.updated_at','r.name as role_name');
        if ($request->filled('keyword')) $query->where(function($q)use($request){$q->where('a.username','like','%'.$request->keyword.'%')->orWhere('a.nickname','like','%'.$request->keyword.'%');});
        $total = $query->count(); $page=$request->get('page',1); $limit=$request->get('limit',20);
        $list = $query->orderBy('a.id','asc')->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function store(Request $request) {
        $v = $request->validate(['username'=>'required|string|unique:admins,username','password'=>'required|string|min:6','nickname'=>'nullable|string','role_id'=>'nullable|integer','status'=>'nullable|integer']);
        $v['password'] = Hash::make($v['password']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('admins')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function update(Request $request, $id) {
        $v = $request->validate(['nickname'=>'sometimes|nullable|string','role_id'=>'sometimes|nullable|integer','status'=>'sometimes|integer']);
        if ($request->filled('password')) $v['password'] = Hash::make($request->password);
        $v['updated_at']=now();
        DB::table('admins')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function destroy($id) {
        if ($id==1) return $this->error('不能删除超级管理员');
        DB::table('admins')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
