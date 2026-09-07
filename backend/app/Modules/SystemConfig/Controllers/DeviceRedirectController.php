<?php
namespace App\Modules\SystemConfig\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceRedirectController extends BaseController
{
    // 获取设备跳转配置
    public function getConfig()
    {
        $configs = DB::table('system_configs')
            ->where('group', 'device_redirect')
            ->pluck('value', 'key')
            ->toArray();

        $default = [
            'pc_redirect_url' => '/pc/',
            'mobile_redirect_url' => '/pc/',
            'tablet_redirect_url' => '/pc/',
            'auto_redirect_enabled' => '1',
            'default_redirect_url' => '/pc/',
        ];

        $result = array_merge($default, $configs);

        return $this->success($result);
    }

    // 保存设备跳转配置
    public function saveConfig(Request $request)
    {
        $data = $request->only([
            'pc_redirect_url',
            'mobile_redirect_url',
            'tablet_redirect_url',
            'auto_redirect_enabled',
            'default_redirect_url',
        ]);

        foreach ($data as $key => $value) {
            DB::table('system_configs')
                ->where('group', 'device_redirect')
                ->where('key', $key)
                ->update([
                    'value' => $value ?? '',
                    'updated_at' => now(),
                ]);
        }

        return $this->success(null, '配置保存成功');
    }

    // 公开API：获取设备跳转配置（供前端智能入口使用）
    public function getPublicConfig()
    {
        return $this->getConfig();
    }
}
