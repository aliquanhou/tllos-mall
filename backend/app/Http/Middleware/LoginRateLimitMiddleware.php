<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoginRateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $key = 'login_attempts:' . $ip;
        $attempts = Cache::get($key, 0);

        if ($attempts >= 5) {
            return response()->json([
                'code' => 429,
                'message' => '登录失败次数过多，请15分钟后再试',
                'data' => null,
                'timestamp' => time(),
            ], 429);
        }

        $response = $next($request);

        if ($response->getStatusCode() == 401 || (json_decode($response->getContent(), true)['code'] ?? 200) == 401) {
            Cache::put($key, $attempts + 1, now()->addMinutes(15));
        } else {
            Cache::forget($key);
        }

        return $response;
    }
}
