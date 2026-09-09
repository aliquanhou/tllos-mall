<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $ip = $request->ip();
        $key = 'rate_limit:' . $ip . ':' . $request->path();

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            Log::warning('请求频率超限', ['ip' => $ip, 'path' => $request->path(), 'attempts' => $attempts]);
            return response()->json([
                'code' => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data' => null,
                'timestamp' => time(),
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return $next($request);
    }
}
