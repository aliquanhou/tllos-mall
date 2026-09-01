<?php
namespace App\Modules\Permission\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends BaseController
{
    public function roleList() {
        $list = DB::table('admin_roles')->orderBy('id','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function roleStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','description'=>'nullable|string','permissions'=>'nullable','status'=>'nullable|integer']);
        if (isset($v['permissions']) && is_array($v['permissions'])) $v['permissions'] = json_encode($v['permissions']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('admin_roles')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function roleUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','description'=>'sometimes|nullable|string','permissions'=>'sometimes|nullable','status'=>'sometimes|integer']);
        if (isset($v['permissions']) && is_array($v['permissions'])) $v['permissions'] = json_encode($v['permissions']);
        $v['updated_at']=now();
        DB::table('admin_roles')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function roleDestroy($id) { DB::table('admin_roles')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }

    public function menuList() {
        $menus = [
            ['id'=>1,'name'=>'工作台','path'=>'/dashboard','icon'=>'Monitor','parent_id'=>0],
            ['id'=>2,'name'=>'商品管理','path'=>'/product','icon'=>'Goods','parent_id'=>0,'children'=>[
                ['id'=>21,'name'=>'商品列表','path'=>'/product/list','parent_id'=>2],
                ['id'=>22,'name'=>'商品分类','path'=>'/product/category','parent_id'=>2],
                ['id'=>23,'name'=>'商品评价','path'=>'/product/comment','parent_id'=>2],
            ]],
            ['id'=>3,'name'=>'订单管理','path'=>'/order','icon'=>'List','parent_id'=>0,'children'=>[
                ['id'=>31,'name'=>'订单列表','path'=>'/order/list','parent_id'=>3],
            ]],
            ['id'=>4,'name'=>'商家管理','path'=>'/merchant','icon'=>'Shop','parent_id'=>0,'children'=>[
                ['id'=>41,'name'=>'商家列表','path'=>'/merchant/list','parent_id'=>4],
                ['id'=>42,'name'=>'入驻审核','path'=>'/merchant/audit','parent_id'=>4],
            ]],
            ['id'=>5,'name'=>'用户管理','path'=>'/user','icon'=>'User','parent_id'=>0,'children'=>[
                ['id'=>51,'name'=>'用户列表','path'=>'/user/list','parent_id'=>5],
            ]],
            ['id'=>6,'name'=>'分销管理','path'=>'/distribute','icon'=>'Share','parent_id'=>0,'children'=>[
                ['id'=>61,'name'=>'分销概览','path'=>'/distribute/overview','parent_id'=>6],
                ['id'=>62,'name'=>'分销商','path'=>'/distribute/agent','parent_id'=>6],
                ['id'=>63,'name'=>'分销等级','path'=>'/distribute/level','parent_id'=>6],
                ['id'=>64,'name'=>'分销订单','path'=>'/distribute/order','parent_id'=>6],
                ['id'=>65,'name'=>'分销商品','path'=>'/distribute/goods','parent_id'=>6],
                ['id'=>66,'name'=>'分销设置','path'=>'/distribute/setting','parent_id'=>6],
            ]],
            ['id'=>7,'name'=>'营销管理','path'=>'/marketing','icon'=>'Present','parent_id'=>0,'children'=>[
                ['id'=>71,'name'=>'优惠券','path'=>'/marketing/coupon','parent_id'=>7],
                ['id'=>72,'name'=>'限时秒杀','path'=>'/marketing/seckill','parent_id'=>7],
                ['id'=>73,'name'=>'拼团活动','path'=>'/marketing/group','parent_id'=>7],
                ['id'=>74,'name'=>'会员折扣','path'=>'/marketing/member-discount','parent_id'=>7],
            ]],
            ['id'=>8,'name'=>'应用管理','path'=>'/application','icon'=>'Grid','parent_id'=>0,'children'=>[
                ['id'=>81,'name'=>'充值管理','path'=>'/application/deposit','parent_id'=>8],
                ['id'=>82,'name'=>'素材管理','path'=>'/application/material','parent_id'=>8],
                ['id'=>83,'name'=>'文章资讯','path'=>'/application/article','parent_id'=>8],
                ['id'=>84,'name'=>'消息管理','path'=>'/application/notice','parent_id'=>8],
                ['id'=>85,'name'=>'商品采集','path'=>'/application/collect','parent_id'=>8],
                ['id'=>86,'name'=>'客服设置','path'=>'/application/kefu','parent_id'=>8],
            ]],
            ['id'=>9,'name'=>'装修管理','path'=>'/decoration','icon'=>'Picture','parent_id'=>0],
            ['id'=>10,'name'=>'财务管理','path'=>'/finance','icon'=>'Money','parent_id'=>0,'children'=>[
                ['id'=>101,'name'=>'订单收款','path'=>'/finance/income','parent_id'=>10],
                ['id'=>102,'name'=>'退款记录','path'=>'/finance/refund','parent_id'=>10],
                ['id'=>103,'name'=>'提现管理','path'=>'/finance/withdraw','parent_id'=>10],
                ['id'=>104,'name'=>'商家结算','path'=>'/finance/settlement','parent_id'=>10],
            ]],
            ['id'=>11,'name'=>'渠道设置','path'=>'/channel','icon'=>'Connection','parent_id'=>0],
            ['id'=>12,'name'=>'组织管理','path'=>'/organization','icon'=>'OfficeBuilding','parent_id'=>0],
            ['id'=>13,'name'=>'权限管理','path'=>'/permission','icon'=>'Lock','parent_id'=>0,'children'=>[
                ['id'=>131,'name'=>'角色管理','path'=>'/permission/role','parent_id'=>13],
                ['id'=>132,'name'=>'菜单管理','path'=>'/permission/menu','parent_id'=>13],
            ]],
            ['id'=>14,'name'=>'系统设置','path'=>'/system','icon'=>'Setting','parent_id'=>0,'children'=>[
                ['id'=>141,'name'=>'基础配置','path'=>'/system/config','parent_id'=>14],
                ['id'=>142,'name'=>'支付配置','path'=>'/system/payment','parent_id'=>14],
                ['id'=>143,'name'=>'物流配置','path'=>'/system/express','parent_id'=>14],
                ['id'=>144,'name'=>'操作日志','path'=>'/system/log','parent_id'=>14],
            ]],
        ];
        return $this->success(['list'=>$menus,'total'=>count($menus)]);
    }

    public function deptList() {
        $list = DB::table('departments')->orderBy('sort','asc')->get();
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function deptStore(Request $request) {
        $v = $request->validate(['name'=>'required|string','parent_id'=>'nullable|integer','sort'=>'nullable|integer','status'=>'nullable|integer']);
        $v['created_at']=now(); $v['updated_at']=now();
        $id = DB::table('departments')->insertGetId($v);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function deptUpdate(Request $request, $id) {
        $v = $request->validate(['name'=>'sometimes|required|string','sort'=>'sometimes|integer','status'=>'sometimes|integer']);
        $v['updated_at']=now();
        DB::table('departments')->where('id',$id)->update($v);
        return $this->success(null,'更新成功');
    }
    public function deptDestroy($id) { DB::table('departments')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
