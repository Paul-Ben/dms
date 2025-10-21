<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP)
        // Adjust this policy based on your application's specific needs
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://ajax.googleapis.com https://stackpath.bootstrapcdn.com https://cdn.datatables.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://cdn.datatables.net; " .
               "img-src 'self' data: https: blob: https://www.google.com https://www.gstatic.com https://res.cloudinary.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "connect-src 'self' https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://fonts.googleapis.com https://fonts.gstatic.com https://res.cloudinary.com; " .
               "frame-src 'self' blob: https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://res.cloudinary.com; " .
               "child-src 'self' blob: https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://res.cloudinary.com; " .
               "worker-src 'self' blob:; " .
               "media-src 'self' https://res.cloudinary.com; " .
               "object-src 'none'; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self' https://www.google.com;";

        // Set security headers
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Additional security headers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // HSTS (HTTP Strict Transport Security) - only for HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
