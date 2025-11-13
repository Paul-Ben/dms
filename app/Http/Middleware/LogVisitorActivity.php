<?php

namespace App\Http\Middleware;

use App\Models\VisitorActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class LogVisitorActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $browser = $this->getBrowser($userAgent);
        $device = $this->getDevice($userAgent);

        // Use a free IP geolocation API (e.g., ip-api.com), with defensive fallbacks
        $location = cache()->remember("ip-location-{$ip}", 60*60, function () use ($ip) {
            try {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
                return $response->successful() ? $response->json() : [];
            } catch (\Throwable $e) {
                return [];
            }
        });

        VisitorActivity::create([
            'user_id' => Auth::id(),
            'ip_address' => $ip,
            'country' => $location['country'] ?? null,
            'region' => $location['regionName'] ?? null,
            'city' => $location['city'] ?? null,
            'browser' => $browser,
            'device' => $device,
            // Avoid overly long URLs (e.g., large query strings from DataTables)
            'url' => Str::limit($request->url(), 255),
            'method' => $request->method(),
            'user_agent' => $userAgent,
        ]);
        return $next($request);
    }
    private function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        return 'Other';
    }

    private function getDevice($userAgent)
    {
        if (preg_match('/mobile/i', $userAgent)) return 'Mobile';
        if (preg_match('/tablet/i', $userAgent)) return 'Tablet';
        return 'Desktop';
    }
}
