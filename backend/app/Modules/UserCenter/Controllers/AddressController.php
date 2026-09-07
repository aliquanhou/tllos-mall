<?php
namespace App\Modules\UserCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends BaseController {
    // 地址列表
    public function lists(Request $request) {
        $userId = $request->user()->id;
        $list = DB::table('user_addresses')
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        return $this->success($list);
    }

    // 添加地址
    public function add(Request $request) {
        $userId = $request->user()->id;
        $data = $request->only(['name', 'mobile', 'province_id', 'province_name', 'city_id', 'city_name', 'district_id', 'district_name', 'detail', 'is_default']);

        if (empty($data['name'])) return $this->error('请输入收货人姓名');
        if (empty($data['mobile'])) return $this->error('请输入手机号');
        if (empty($data['province_name']) && empty($data['city_name'])) return $this->error('请选择所在地区');
        if (empty($data['detail'])) return $this->error('请输入详细地址');

        $data['user_id'] = $userId;
        $data['province_id'] = $data['province_id'] ?? 0;
        $data['city_id'] = $data['city_id'] ?? 0;
        $data['district_id'] = $data['district_id'] ?? 0;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            if (!empty($data['is_default'])) {
                DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => 0]);
            } else {
                $count = DB::table('user_addresses')->where('user_id', $userId)->count();
                if ($count == 0) $data['is_default'] = 1;
            }

            $id = DB::table('user_addresses')->insertGetId($data);
            DB::commit();
            return $this->success(['id' => $id], '添加成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('添加失败: ' . $e->getMessage());
        }
    }

    // 编辑地址
    public function edit(Request $request, $id) {
        $userId = $request->user()->id;
        $address = DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->first();
        if (!$address) return $this->error('地址不存在');

        $data = $request->only(['name', 'mobile', 'province_id', 'province_name', 'city_id', 'city_name', 'district_id', 'district_name', 'detail', 'is_default']);
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            if (!empty($data['is_default'])) {
                DB::table('user_addresses')->where('user_id', $userId)->where('id', '!=', $id)->update(['is_default' => 0]);
            }
            DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->update($data);
            DB::commit();
            return $this->success(null, '修改成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('修改失败: ' . $e->getMessage());
        }
    }

    // 删除地址
    public function delete(Request $request, $id) {
        $userId = $request->user()->id;
        DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->delete();
        return $this->success(null, '删除成功');
    }

    // 地址详情
    public function detail(Request $request, $id) {
        $userId = $request->user()->id;
        $addr = DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->first();
        if (!$addr) return $this->error('地址不存在');
        return $this->success($addr);
    }

    // 设置默认地址
    public function setDefault(Request $request, $id) {
        $userId = $request->user()->id;
        DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => 0]);
        DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->update(['is_default' => 1]);
        return $this->success(null, '设置成功');
    }

    // 获取默认地址
    public function getDefault(Request $request) {
        $userId = $request->user()->id;
        $addr = DB::table('user_addresses')->where('user_id', $userId)->where('is_default', 1)->first();
        return $this->success($addr);
    }
}
