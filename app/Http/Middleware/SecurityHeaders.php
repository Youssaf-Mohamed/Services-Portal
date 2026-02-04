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

        // SECURITY HEADERS (production security hardening)

        // 1. HSTS - Force HTTPS (only in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // 2. Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // 3. Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 4. Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Content Security Policy (relaxed for API - no restrictions on API responses)
        // NOTE: CSP is primarily for HTML responses. For API, we keep it minimal.
        // $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none';");

        // 6. Permissions Policy (disable unnecessary features)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
