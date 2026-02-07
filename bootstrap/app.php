<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // SECURITY: Add security headers to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'test.mode' => \App\Http\Middleware\TestModeOnly::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'lms.auth' => \App\Http\Middleware\VerifyLmsApiKey::class,
        ]);

        // Exclude SSO endpoints from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/sso/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            // Only handle API requests
            if ($request->is('api/*')) {
                // Validation errors (422)
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return \App\Support\ApiResponse::error(
                        'Validation failed',
                        $e->errors(),
                        422
                    );
                }

                // Authentication errors (401)
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return \App\Support\ApiResponse::error(
                        'Unauthenticated',
                        null,
                        401
                    );
                }

                // Authorization errors (403)
                if (
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
                    $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
                ) {
                    return \App\Support\ApiResponse::error(
                        'Unauthorized',
                        null,
                        403
                    );
                }

                // Not found errors (404)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return \App\Support\ApiResponse::error(
                        'Resource not found',
                        null,
                        404
                    );
                }

                // Server errors (500)
                if (!config('app.debug')) {
                    return \App\Support\ApiResponse::error(
                        'Server error',
                        null,
                        500
                    );
                }
            }
        });
    })->create();
