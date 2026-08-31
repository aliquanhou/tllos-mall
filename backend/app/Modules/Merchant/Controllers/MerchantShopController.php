<?php
namespace App\Modules\Merchant\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantShopController extends BaseController {
    public function detail(Request $request) {
        $shop = DB::table('shops')->where('user_id',$request->user()->id)->first();
        return $this->success($shop);
    }
    public function edit(Request $request) {
        $shopId = DB::table('shops')->where('user_id',$request->user()->id)->value('id');
        $data = $request->only(['name','logo','description','contact_name','contact_phone','address','business_hours']);
        $data['updated_at'] = now();
        DB::table('shops')->where('id',$shopId)->update($data);
        return $this->success(null,'修改成功');
    }
}
