<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ApiCache
{
    /**
     * 缓存时间（秒）
     */
    protected $cacheTime = 300; // 5分钟

    /**
     * 不需要缓存的路由
     */
    protected $except = [
        'api/v1/auth/login',
        'api/v1/auth/register',
        'api/v1/auth/logout',
        'api/v1/cart',
        'api/v1/orders',
        'api/v1/payment',
        'api/v1/comments',
        'api/v1/user',
        'api/v1/address',
        'api/v1/collects',
    ];

    /**
     * 处理传入请求
     */
    public function handle(Request $request, Closure $next, $cacheTime = null)
    {
        // 只缓存GET请求
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // 检查是否在排除列表中
        $currentPath = $request->path();
        foreach ($this->except as $except) {
            if (strpos($currentPath, $except) !== false) {
                return $next($request);
            }
        }

        // 生成缓存键
        $cacheKey = 'api_cache:' . md5($request->fullUrl());

        // 检查缓存
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            return response()->json($cachedData)
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // 处理请求
        $response = $next($request);

        // 只缓存成功的JSON响应
        if ($response->getStatusCode() === 200 && strpos($response->headers->get('Content-Type', ''), 'application/json') !== false) {
            $responseData = json_decode($response->getContent(), true);
            if ($responseData) {
                $time = $cacheTime ? intval($cacheTime) : $this->cacheTime;
                Cache::put($cacheKey, $responseData, $time);
                $response->header('X-Cache', 'MISS')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Cache-TTL', $time);
            }
        }

        return $response;
    }
}
