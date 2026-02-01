<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class TestModeOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (env('APP_MODE') !== 'test') {
            return ApiResponse::error('Not found', null, 404);
        }

        return $next($request);
    }
}
