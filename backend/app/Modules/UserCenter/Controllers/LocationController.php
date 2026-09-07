<?php
namespace App\Modules\UserCenter\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocationController extends BaseController
{
    /**
     * 获取地理位置（IP定位 + 地图key定位）
     * 优先使用地图key精确定位，如果未配置则使用IP定位
     */
    public function getLocation(Request $request)
    {
        // 获取客户端IP
        $ip = $request->ip();
        if ($ip == '127.0.0.1' || $ip == '::1') {
            $ip = $request->header('X-Forwarded-For') ?: $request->header('X-Real-IP') ?: '8.8.8.8';
        }

        // 获取地图配置
        $mapProvider = $this->getConfig('map_provider', 'amap');
        $ipEnabled = $this->getConfig('ip_location_enabled', '1');

        $result = [
            'ip' => $ip,
            'provider' => null,
            'province' => null,
            'city' => null,
            'district' => null,
            'address' => null,
            'latitude' => null,
            'longitude' => null,
            'accuracy' => 'low'
        ];

        // 1. 优先尝试地图key精确定位
        $mapResult = $this->mapLocation($ip, $mapProvider);
        if ($mapResult) {
            $result = array_merge($result, $mapResult);
            return $this->success($result, '地图定位成功');
        }

        // 2. 如果地图定位失败且启用了IP定位，使用IP定位
        if ($ipEnabled == '1') {
            $ipResult = $this->ipLocation($ip);
            if ($ipResult) {
                $result = array_merge($result, $ipResult);
                return $this->success($result, 'IP定位成功');
            }
        }

        return $this->error('定位失败，请手动选择地址');
    }

    /**
     * 地图key定位
     */
    private function mapLocation($ip, $provider)
    {
        $key = null;
        $url = null;

        switch ($provider) {
            case 'amap':
                $key = $this->getConfig('amap_key');
                if (!$key) return null;
                $url = "https://restapi.amap.com/v3/ip?key={$key}&ip={$ip}";
                break;
            case 'tencent':
                $key = $this->getConfig('tencent_map_key');
                if (!$key) return null;
                $url = "https://apis.map.qq.com/ws/location/v1/ip?key={$key}&ip={$ip}";
                break;
            case 'baidu':
                $key = $this->getConfig('baidu_map_key');
                if (!$key) return null;
                $url = "https://api.map.baidu.com/location/ip?ak={$key}&ip={$ip}&coor=bd09ll";
                break;
            default:
                return null;
        }

        try {
            $response = Http::timeout(5)->get($url);
            $data = $response->json();

            if (!$data) return null;

            // 高德地图返回格式
            if ($provider == 'amap' && isset($data['status']) && $data['status'] == '1') {
                $province = $data['province'] ?? null;
                $city = $data['city'] ?? null;
                $rectangle = $data['rectangle'] ?? null;
                $longitude = null;
                $latitude = null;
                if ($rectangle) {
                    $parts = explode(';', $rectangle);
                    if (count($parts) >= 2) {
                        $center = explode(',', $parts[0]);
                        if (count($center) == 2) {
                            $longitude = floatval($center[0]);
                            $latitude = floatval($center[1]);
                        }
                    }
                }
                return [
                    'provider' => 'amap',
                    'province' => $province,
                    'city' => $city,
                    'district' => null,
                    'address' => $province . $city,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy' => 'city'
                ];
            }

            // 腾讯地图返回格式
            if ($provider == 'tencent' && isset($data['status']) && $data['status'] == 0) {
                $result = $data['result'] ?? [];
                $location = $result['location'] ?? [];
                $adInfo = $result['ad_info'] ?? [];
                return [
                    'provider' => 'tencent',
                    'province' => $adInfo['province'] ?? null,
                    'city' => $adInfo['city'] ?? null,
                    'district' => $adInfo['district'] ?? null,
                    'address' => $result['address'] ?? null,
                    'latitude' => $location['lat'] ?? null,
                    'longitude' => $location['lng'] ?? null,
                    'accuracy' => 'district'
                ];
            }

            // 百度地图返回格式
            if ($provider == 'baidu' && isset($data['status']) && $data['status'] == 0) {
                $content = $data['content'] ?? [];
                $addressDetail = $content['address_detail'] ?? [];
                $point = $content['point'] ?? [];
                return [
                    'provider' => 'baidu',
                    'province' => $addressDetail['province'] ?? null,
                    'city' => $addressDetail['city'] ?? null,
                    'district' => $addressDetail['district'] ?? null,
                    'address' => $content['address'] ?? null,
                    'latitude' => isset($point['y']) ? floatval($point['y']) : null,
                    'longitude' => isset($point['x']) ? floatval($point['x']) : null,
                    'accuracy' => 'district'
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * IP定位（使用免费的ip-api.com）
     */
    private function ipLocation($ip)
    {
        try {
            // 使用ip-api.com免费API（不需要key）
            $url = "http://ip-api.com/json/{$ip}?lang=zh-CN&fields=status,country,regionName,city,district,lat,lon,query";
            $response = Http::timeout(5)->get($url);
            $data = $response->json();

            if (!$data || !isset($data['status']) || $data['status'] != 'success') {
                // 备用：使用ipinfo.io
                $url2 = "https://ipinfo.io/{$ip}/json";
                $response2 = Http::timeout(5)->get($url2);
                $data2 = $response2->json();
                if ($data2 && isset($data2['city'])) {
                    $loc = isset($data2['loc']) ? explode(',', $data2['loc']) : [null, null];
                    return [
                        'provider' => 'ipinfo',
                        'province' => $data2['region'] ?? null,
                        'city' => $data2['city'] ?? null,
                        'district' => null,
                        'address' => ($data2['city'] ?? '') . ($data2['region'] ?? ''),
                        'latitude' => isset($loc[0]) ? floatval($loc[0]) : null,
                        'longitude' => isset($loc[1]) ? floatval($loc[1]) : null,
                        'accuracy' => 'city'
                    ];
                }
                return null;
            }

            return [
                'provider' => 'ip-api',
                'province' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'address' => ($data['regionName'] ?? '') . ($data['city'] ?? '') . ($data['district'] ?? ''),
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lon'] ?? null,
                'accuracy' => 'city'
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取系统配置
     */
    private function getConfig($key, $default = null)
    {
        static $configs = null;
        if ($configs === null) {
            $configs = [];
            $rows = DB::table('system_configs')->where('status', 1)->get();
            foreach ($rows as $row) {
                $configs[$row->key] = $row->value;
            }
        }
        return $configs[$key] ?? $default;
    }

    /**
     * 获取地图配置状态（前端用于显示是否已配置地图key）
     */
    public function getMapConfigStatus()
    {
        return $this->success([
            'provider' => $this->getConfig('map_provider', 'amap'),
            'amap_key_configured' => !empty($this->getConfig('amap_key')),
            'tencent_map_key_configured' => !empty($this->getConfig('tencent_map_key')),
            'baidu_map_key_configured' => !empty($this->getConfig('baidu_map_key')),
            'ip_location_enabled' => $this->getConfig('ip_location_enabled', '1') == '1'
        ]);
    }
}
