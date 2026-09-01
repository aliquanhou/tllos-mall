<?php
namespace App\Modules\Merchant\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantLevelController extends BaseController {
    public function index() { return $this->success(DB::table('merchant_levels')->orderBy('sort','asc')->get()); }
    public function store(Request $request) {
        $data = $request->only(['name','description','commission_rate','sort','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('merchant_levels')->insertGetId($data);
        return $this->success(DB::table('merchant_levels')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','description','commission_rate','sort','status']);
        $data['updated_at'] = now();
        DB::table('merchant_levels')->where('id',$id)->update($data);
        return $this->success(DB::table('merchant_levels')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('merchant_levels')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
}
