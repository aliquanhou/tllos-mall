<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RateLimiterMiddleware
{
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestKey($request);
        $maxAttempts = (int)$maxAttempts;
        $decayMinutes = (int)$decayMinutes;

        $cacheKey = 'rate_limit:' . $key;
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = $decayMinutes * 60;
            throw new TooManyRequestsHttpException($retryAfter, '请求过于频繁，请稍后再试');
        }

        Cache::put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $attempts - 1));

        return $response;
    }

    private function resolveRequestKey(Request $request)
    {
        $ip = $request->ip();
        $path = $request->path();
        $userId = $request->user()?->id ?? 'guest';
        return md5($ip . '|' . $path . '|' . $userId);
    }
}
