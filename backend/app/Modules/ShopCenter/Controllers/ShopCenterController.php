<?php
namespace App\Modules\ShopCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ShopCenterController extends BaseController {
    public function categories() {
        try {
            $list = DB::table('shop_categories')->orderBy('sort','asc')->get();
            return $this->success(['list'=>$list,'total'=>count($list)]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function categoryStore(Request $request) {
        $data = $request->only(['name','sort','status']);
        $data['created_at'] = now();
        $id = DB::table('shop_categories')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function categoryUpdate(Request $request, $id) {
        $data = $request->only(['name','sort','status']);
        DB::table('shop_categories')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function categoryDestroy($id) {
        DB::table('shop_categories')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }

    public function accountLogs(Request $request) {
        try {
            $query = DB::table('merchant_account_logs');
            if ($request->filled('shop_id')) $query->where('merchant_id', $request->shop_id);
            if ($request->filled('type')) $query->where('type', $request->type);
            $total = $query->count();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $list = $query->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit)->get();
            return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
}
