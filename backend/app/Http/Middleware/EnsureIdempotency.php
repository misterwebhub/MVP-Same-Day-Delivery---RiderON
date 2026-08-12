<?php

namespace App\Http\Middleware;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\IdempotencyKeyRequiredException;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the Idempotency-Key contract from docs/03-api-architecture.md for
 * mutating endpoints (POST /orders, POST /payments/*\/verify, POST
 * /orders/{id}/cancel). A repeat request with the same key + method + path
 * replays the original response instead of re-running the handler.
 *
 * Uses the generic Cache facade (not a Redis-specific API) so this works
 * identically on the `database` cache driver used locally and `redis` in
 * staging/prod. Cache::lock() closes the race window between two
 * near-simultaneous requests carrying the same key.
 */
class EnsureIdempotency
{
    private const LOCK_SECONDS = 10;

    private const RESPONSE_TTL_SECONDS = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            throw new IdempotencyKeyRequiredException();
        }

        $cacheKey = $this->cacheKey($request, $idempotencyKey);

        if (($cached = Cache::get($cacheKey)) !== null) {
            return $this->replay($cached);
        }

        try {
            return Cache::lock($cacheKey.':lock', self::LOCK_SECONDS)
                ->block(self::LOCK_SECONDS, function () use ($request, $next, $cacheKey) {
                    if (($cached = Cache::get($cacheKey)) !== null) {
                        return $this->replay($cached);
                    }

                    $response = $next($request);

                    if ($response->getStatusCode() < 500) {
                        Cache::put($cacheKey, [
                            'status' => $response->getStatusCode(),
                            'content' => $response->getContent(),
                        ], self::RESPONSE_TTL_SECONDS);
                    }

                    return $response;
                });
        } catch (LockTimeoutException) {
            throw new IdempotencyConflictException();
        }
    }

    private function cacheKey(Request $request, string $idempotencyKey): string
    {
        return 'idempotency:'.sha1($request->method().'|'.$request->path().'|'.$idempotencyKey);
    }

    private function replay(array $cached): Response
    {
        return response($cached['content'], $cached['status'])
            ->header('Content-Type', 'application/json');
    }
}
