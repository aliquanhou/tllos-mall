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

    // 统一字段归一化（兼容fuduoduo风格和我们自己的风格）
    private function normalizeAddressData($data) {
        // 收货人：兼容 name 和 consignee
        $name = $data['name'] ?? $data['consignee'] ?? '';
        // 省：兼容 province_name 和 province
        $provinceName = $data['province_name'] ?? $data['province'] ?? '';
        // 市：兼容 city_name 和 city
        $cityName = $data['city_name'] ?? $data['city'] ?? '';
        // 区：兼容 district_name 和 district
        $districtName = $data['district_name'] ?? $data['district'] ?? '';
        // 详细地址：兼容 detail 和 address
        $detail = $data['detail'] ?? $data['address'] ?? '';

        return [
            'name' => $name,
            'mobile' => $data['mobile'] ?? '',
            'province_id' => $data['province_id'] ?? 0,
            'province_name' => $provinceName,
            'city_id' => $data['city_id'] ?? 0,
            'city_name' => $cityName,
            'district_id' => $data['district_id'] ?? 0,
            'district_name' => $districtName,
            'detail' => $detail,
            'is_default' => $data['is_default'] ?? 0,
        ];
    }

    // 统一校验方法（放宽地区校验：detail不为空时允许保存）
    private function validateAddress($data) {
        if (empty($data['name'])) return '请输入收货人姓名';
        if (mb_strlen($data['name']) > 50) return '收货人姓名不能超过50个字符';
        if (empty($data['mobile'])) return '请输入手机号';
        if (!preg_match('/^1[3-9]\d{9}$/', $data['mobile'])) return '手机号格式不正确，请输入11位有效手机号';
        // 放宽地区校验：如果详细地址不为空，允许省市区为空（兼容定位失败的情况）
        if (empty($data['province_name']) && empty($data['city_name']) && empty($data['detail'])) {
            return '请选择所在地区或填写详细地址';
        }
        if (empty($data['detail'])) return '请输入详细地址';
        if (mb_strlen($data['detail']) > 255) return '详细地址不能超过255个字符';
        return null;
    }

    // 添加地址
    public function add(Request $request) {
        $userId = $request->user()->id;
        $rawData = $request->only(['name', 'consignee', 'mobile', 'province_id', 'province_name', 'province', 'city_id', 'city_name', 'city', 'district_id', 'district_name', 'district', 'detail', 'address', 'is_default']);

        // 字段归一化（兼容fuduoduo风格）
        $data = $this->normalizeAddressData($rawData);

        // 后端统一校验
        $error = $this->validateAddress($data);
        if ($error) return $this->error($error);

        $data['user_id'] = $userId;
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

        $rawData = $request->only(['name', 'consignee', 'mobile', 'province_id', 'province_name', 'province', 'city_id', 'city_name', 'city', 'district_id', 'district_name', 'district', 'detail', 'address', 'is_default']);
        $data = $this->normalizeAddressData($rawData);
        $data['updated_at'] = now();

        // 后端统一校验
        $error = $this->validateAddress($data);
        if ($error) return $this->error($error);

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
        $address = DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->first();
        if (!$address) return $this->error('地址不存在');
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
