<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLmsApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-LMS-API-KEY');
        $validKey = config('services.lms.api_key', env('LMS_API_KEY'));

        if (!$apiKey || !$validKey || $apiKey !== $validKey) {
            return ApiResponse::error('Unauthorized: Invalid API Key', null, 401);
        }

        return $next($request);
    }
}
