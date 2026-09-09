<?php
namespace App\Modules\SystemConfig\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapConfigController extends BaseController
{
    // 获取地图配置列表
    public function index()
    {
        $configs = DB::table('system_configs')
            ->where('group', 'map')
            ->orderBy('sort', 'asc')
            ->get();
        return $this->success(['list' => $configs]);
    }

    // 保存地图配置
    public function save(Request $request)
    {
        $configs = $request->input('configs', []);
        if (empty($configs)) {
            return $this->error('配置数据不能为空');
        }

        DB::beginTransaction();
        try {
            foreach ($configs as $key => $value) {
                $exists = DB::table('system_configs')
                    ->where('group', 'map')
                    ->where('key', $key)
                    ->first();

                $data = [
                    'group' => 'map',
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'updated_at' => now(),
                ];

                if ($exists) {
                    DB::table('system_configs')
                        ->where('id', $exists->id)
                        ->update($data);
                } else {
                    $data['name'] = $this->getConfigName($key);
                    $data['type'] = 'text';
                    $data['sort'] = $this->getConfigSort($key);
                    $data['status'] = 1;
                    $data['created_at'] = now();
                    DB::table('system_configs')->insert($data);
                }
            }
            DB::commit();
            return $this->success(null, '保存成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('保存失败: ' . $e->getMessage());
        }
    }

    // 获取配置名称
    private function getConfigName($key)
    {
        $names = [
            'map_provider' => '地图服务商',
            'amap_key' => '高德地图Key',
            'tencent_map_key' => '腾讯地图Key',
            'baidu_map_key' => '百度地图Key',
            'ip_location_enabled' => 'IP定位开关',
            'map_location_enabled' => '地图定位开关',
        ];
        return $names[$key] ?? $key;
    }

    // 获取配置排序
    private function getConfigSort($key)
    {
        $sorts = [
            'map_provider' => 1,
            'amap_key' => 2,
            'tencent_map_key' => 3,
            'baidu_map_key' => 4,
            'ip_location_enabled' => 5,
            'map_location_enabled' => 6,
        ];
        return $sorts[$key] ?? 10;
    }

    // 获取地图配置（供LocationController使用）
    public static function getMapConfigs()
    {
        $configs = DB::table('system_configs')
            ->where('group', 'map')
            ->where('status', 1)
            ->get();
        $result = [];
        foreach ($configs as $cfg) {
            $result[$cfg->key] = $cfg->value;
        }
        return $result;
    }
}
