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
 * setiap kali ada request masuk. Jika limit tercapai, user HARUS menunggu
 * penuh dari waktu request TERAKHIR, bukan dari request pertama.
 *
 * Algoritma:
 * 1. Setiap request masuk → increment counter + reset TTL ke {decaySeconds}.
 * 2. Jika counter > maxAttempts → tolak dengan 429 + Retry-After = decaySeconds.
 * 3. Counter hanya expire jika user TIDAK melakukan request selama {decaySeconds} detik.
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

            $attempts = (int) Cache::get($key, 0);
            $attempts++;

            // Selalu simpan counter baru dengan TTL yang di-reset (sliding window)
            // TTL selalu dihitung dari NOW + decaySeconds, bukan dari request pertama
            Cache::put($key, $attempts, now()->addSeconds($decaySeconds));

            if ($attempts > $maxAttempts) {
                return $this->buildTooManyAttemptsResponse($decaySeconds, $maxAttempts);
            }

            $response = $next($request);

            return $this->addRateLimitHeaders(
                $response,
                $maxAttempts,
                $maxAttempts - $attempts,
            );
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Jika gagal acquire lock (server sangat sibuk), tolak juga dengan 429
            return $this->buildTooManyAttemptsResponse($decaySeconds, $maxAttempts);
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
