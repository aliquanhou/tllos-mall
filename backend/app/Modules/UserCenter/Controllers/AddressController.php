<?php
namespace App\Modules\UserCenter\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AddressController extends BaseController {
    public function lists(Request $request) {
        $userId = $request->user()->id;
        $list = DB::table('user_addresses')->where('user_id', $userId)->orderBy('is_default','desc')->orderBy('id','desc')->get();
        return $this->success($list);
    }
    public function add(Request $request) {
        $userId = $request->user()->id;
        $data = $request->only(['name','mobile','province_id','province_name','city_id','city_name','district_id','district_name','detail','is_default']);
        $data['user_id'] = $userId; $data['created_at'] = now();
        if (!empty($data['is_default'])) DB::table('user_addresses')->where('user_id',$userId)->update(['is_default'=>0]);
        $id = DB::table('user_addresses')->insertGetId($data);
        return $this->success(['id'=>$id],'添加成功');
    }
    public function edit(Request $request, $id) {
        $userId = $request->user()->id;
        $data = $request->only(['name','mobile','province_id','province_name','city_id','city_name','district_id','district_name','detail','is_default']);
        $data['updated_at'] = now();
        if (!empty($data['is_default'])) DB::table('user_addresses')->where('user_id',$userId)->where('id','!=',$id)->update(['is_default'=>0]);
        DB::table('user_addresses')->where('id',$id)->where('user_id',$userId)->update($data);
        return $this->success(null,'修改成功');
    }
    public function delete(Request $request, $id) {
        DB::table('user_addresses')->where('id',$id)->where('user_id',$request->user()->id)->delete();
        return $this->success(null,'删除成功');
    }
    public function detail(Request $request, $id) {
        $addr = DB::table('user_addresses')->where('id',$id)->where('user_id',$request->user()->id)->first();
        return $this->success($addr);
    }
}
