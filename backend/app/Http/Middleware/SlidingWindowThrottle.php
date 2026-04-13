<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sliding Window Throttle Middleware
 *
 * Berbeda dengan Fixed Window bawaan Laravel, middleware ini me-reset TTL
 * setiap kali ada request masuk selama belum mencapai limit. 
 * Jika limit tercapai, user HARUS menunggu sisa waktu dari limit terakhir, 
 * dan counter waktu TIDAK AKAN mengulang (restart) dari awal walau request terus ditekan.
 *
 * Algoritma:
 * 1. Setiap request masuk (< limit) → increment counter + reset TTL ke {decaySeconds}.
 * 2. Jika counter > maxAttempts → tolak dengan 429 + Retry-After sisa waktu. TTL tidak diperpanjang.
 */
class SlidingWindowThrottle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts  Jumlah request maksimum sebelum diblokir
     * @param  int  $decaySeconds Durasi cooldown (dalam detik) dari request terakhir
     * @param  string  $prefix  Prefix untuk cache key (membedakan limiter)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(
        Request $request,
        Closure $next,
        int $maxAttempts = 15,
        int $decaySeconds = 60,
        string $prefix = 'sliding_throttle'
    ): Response {
        $key = $this->resolveRequestKey($request, $prefix);

        // Gunakan atomic lock untuk mencegah race condition pada concurrent requests
        $lockKey = $key . ':lock';
        $lock = Cache::lock($lockKey, 5); // Lock timeout 5 detik

        try {
            // Acquire lock — block max 5 detik agar request lain menunggu giliran
            $lock->block(5);

            $cacheData = Cache::get($key);
            
            if (is_array($cacheData)) {
                $attempts = $cacheData['attempts'] ?? 0;
                $expiresAt = $cacheData['expires_at'] ?? now()->addSeconds($decaySeconds)->timestamp;
            } else {
                $attempts = (int) $cacheData;
                $expiresAt = now()->addSeconds($decaySeconds)->timestamp;
            }

            $attempts++;

            if ($attempts > $maxAttempts) {
                // Limit tercapai. JANGAN mereset/mengulang waktu (TTL).
                // Kita hitung sisa waktu mundur.
                $retryAfter = max(0, $expiresAt - time());

                if ($retryAfter > 0) {
                    Cache::put($key, [
                        'attempts' => $attempts,
                        'expires_at' => $expiresAt
                    ], now()->addSeconds($retryAfter));
                }

                return $this->buildTooManyAttemptsResponse($retryAfter, $maxAttempts);
            }

            // Jika belum limit, maka TTL di-reset ke NOW + decaySeconds. (Sliding)
            $newExpiresAt = now()->addSeconds($decaySeconds)->timestamp;
            Cache::put($key, [
                'attempts' => $attempts,
                'expires_at' => $newExpiresAt
            ], now()->addSeconds($decaySeconds));

            $response = $next($request);

            return $this->addRateLimitHeaders(
                $response,
                $maxAttempts,
                $maxAttempts - $attempts,
            );
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Jika gagal acquire lock (server sangat sibuk), tolak dengan 429
            // Coba ambil sisa waktu re-try jika ada
            $cacheData = Cache::get($key);
            $retryAfter = $decaySeconds;
            if (is_array($cacheData) && isset($cacheData['expires_at'])) {
                $retryAfter = max(0, $cacheData['expires_at'] - time());
            }
            return $this->buildTooManyAttemptsResponse($retryAfter ?: $decaySeconds, $maxAttempts);
        } finally {
            // Pastikan lock selalu dilepas
            optional($lock)->release();
        }
    }

    /**
     * Buat key unik berdasarkan user ID atau IP address.
     */
    protected function resolveRequestKey(Request $request, string $prefix): string
    {
        $identifier = $request->user()?->id ?: $request->ip();

        return $prefix . ':' . sha1($identifier);
    }

    /**
     * Buat response 429 Too Many Requests.
     */
    protected function buildTooManyAttemptsResponse(int $retryAfter, int $maxAttempts): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Terlalu banyak permintaan. Silakan coba lagi dalam {$retryAfter} detik.",
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Tambahkan rate limit headers ke response yang sukses.
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remaining): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }
}
