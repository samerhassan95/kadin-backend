<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CacheApiResponse
{
    /**
     * How long (in seconds) responses should be cached.
     * Default: 5 minutes. Override per-route using the middleware param:
     *   ->middleware('api.cache:300')
     */
    private const DEFAULT_TTL = 300;

    /**
     * URL path segments that should NEVER be cached
     * (e.g. auth, payments, orders, notifications, cart).
     */
    private const SKIP_PATHS = [
        'auth',
        'payment',
        'order',
        'notification',
        'cart',
        'logout',
        'profile',
        'wallet',
        'chat',
        'refund',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $ttl = self::DEFAULT_TTL): SymfonyResponse
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip if the caller explicitly asks for a fresh response
        if ($request->header('Cache-Control') === 'no-cache' || $request->has('no_cache')) {
            return $next($request);
        }

        // Skip if the user is authenticated (avoid leaking user-specific data)
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Skip for sensitive path segments
        $path = strtolower($request->path());
        foreach (self::SKIP_PATHS as $skip) {
            if (str_contains($path, $skip)) {
                return $next($request);
            }
        }

        // Build a unique cache key from method + full URL (including query string)
        $cacheKey = 'api_cache:' . md5($request->method() . '|' . $request->fullUrl());

        // Return cached response if available
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response($cached['content'], $cached['status'])
                ->withHeaders(array_merge($cached['headers'], [
                    'X-Cache'     => 'HIT',
                    'X-Cache-TTL' => $ttl,
                ]));
        }

        // Process the actual request
        /** @var Response $response */
        $response = $next($request);

        // Only cache successful responses (2xx)
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status'  => $response->getStatusCode(),
                'headers' => array_diff_key(
                    $response->headers->all(),
                    // Don't store cookie/auth headers in cache
                    array_flip(['set-cookie', 'authorization'])
                ),
            ], $ttl);

            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-TTL', (string) $ttl);
        }

        return $response;
    }
}
