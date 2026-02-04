<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentTransportRequestController;
use App\Http\Controllers\Api\StudentTransportStatusController;
use App\Http\Controllers\Api\TransportPlanController;
use App\Http\Controllers\Api\Transport\StudentTransportController;
use App\Http\Controllers\Api\Admin\TransportDashboardController;
use App\Http\Controllers\Api\Admin\TransportRequestController;
use App\Http\Controllers\Api\Admin\RouteController;
use App\Http\Controllers\Api\Admin\SlotController;
use App\Http\Controllers\Api\Admin\ManifestController;
use App\Http\Controllers\Api\Admin\BusStopController;
use App\Http\Controllers\Api\Admin\PaymentMethodController;
use App\Http\Controllers\Api\Admin\TransportSettingController;
use App\Http\Controllers\Api\UnifiedRequestsController;
use App\Http\Controllers\Api\IdCard\StudentIdCardController;
use App\Http\Controllers\Api\IdCard\StudentIdCardRequestController;
use App\Http\Controllers\Api\Admin\IdCard\IdCardRequestController;
use App\Http\Controllers\Api\Admin\IdCard\IdCardSettingController;
use App\Support\ApiResponse;

// Health check endpoint (no auth required)
Route::get('/health', function () {
    return ApiResponse::success(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

// Test mode authentication endpoints (rate limited)
Route::middleware(['test.mode', 'throttle:login'])->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/sso/secret-login', [\App\Http\Controllers\Admin\SecretLoginController::class, 'login']);

// SECURITY: SSO verification with rate limiting
Route::middleware('throttle:sso')->get('/sso/verify', [\App\Http\Controllers\SSOController::class, 'verify']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Notifications (available to all authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/ping', [AdminController::class, 'ping']);
        Route::get('/storage-check', [AdminController::class, 'storageCheck']);

        // Admin Transport Management
        Route::prefix('transport')->group(function () {
            // Dashboard
            Route::get('/dashboard', [TransportDashboardController::class, 'dashboard']);
            Route::get('/manifest', [TransportDashboardController::class, 'manifest']);

        });

        // Announcmements
        Route::apiResource('announcements', \App\Http\Controllers\Api\Admin\AnnouncementController::class);

        // Admin Transport Management
        Route::prefix('transport')->group(function () {

            // Reports / Exports
            Route::get('/reports/active-subscriptions', [\App\Http\Controllers\Api\Admin\ReportsController::class, 'exportActiveSubscriptions']);
            Route::get('/reports/waitlist', [\App\Http\Controllers\Api\Admin\ReportsController::class, 'exportWaitlist']);
            Route::get('/manifest/export', [\App\Http\Controllers\Api\Admin\ReportsController::class, 'exportManifest']);

            // Requests management
            Route::get('/requests', [TransportRequestController::class, 'index']);
            Route::get('/requests/{id}', [TransportRequestController::class, 'show']);
            Route::post('/requests/{id}/verify-payment', [TransportRequestController::class, 'verifyPayment']);
            Route::post('/requests/{id}/flag-payment', [TransportRequestController::class, 'flagPayment']);
            Route::post('/requests/{id}/approve', [TransportRequestController::class, 'approve']);
            Route::post('/requests/{id}/reject', [TransportRequestController::class, 'reject']);
            Route::post('/requests/bulk-approve', [TransportRequestController::class, 'bulkApprove']);
            Route::post('/requests/bulk-reject', [TransportRequestController::class, 'bulkReject']);
            Route::get('/requests/{id}/proof', [TransportRequestController::class, 'downloadProof']);

            // Routes CRUD
            Route::get('/routes', [RouteController::class, 'index']);
            Route::post('/routes', [RouteController::class, 'store']);
            Route::get('/routes/{id}', [RouteController::class, 'show']);
            Route::put('/routes/{id}', [RouteController::class, 'update']);
            Route::put('/routes/{id}/stops', [RouteController::class, 'updateStops']);

            // Slots CRUD
            Route::get('/slots', [SlotController::class, 'index']);
            Route::post('/slots', [SlotController::class, 'store']);
            Route::put('/slots/{id}', [SlotController::class, 'update']);

            // Stops CRUD
            Route::apiResource('stops', BusStopController::class);

            // Settings & Payments
            Route::apiResource('payment-methods', PaymentMethodController::class);
            Route::get('/settings', [TransportSettingController::class, 'show']);
            Route::put('/settings', [TransportSettingController::class, 'update']);
        });

        // Admin ID Card Management
        Route::prefix('id-card')->group(function () {
            // Dashboard
            Route::get('/dashboard', [IdCardRequestController::class, 'dashboard']);

            // Requests management
            Route::get('/requests', [IdCardRequestController::class, 'index']);
            Route::get('/requests/{id}', [IdCardRequestController::class, 'show']);
            Route::get('/requests/{id}/attachments/{kind}', [IdCardRequestController::class, 'attachment']);
            Route::post('/requests/{id}/verify-payment', [IdCardRequestController::class, 'verifyPayment']);
            Route::post('/requests/{id}/flag-payment', [IdCardRequestController::class, 'flagPayment']);
            Route::post('/requests/{id}/approve', [IdCardRequestController::class, 'approve']);
            Route::post('/requests/{id}/reject', [IdCardRequestController::class, 'reject']);
            Route::post('/requests/{id}/ready', [IdCardRequestController::class, 'ready']);
            Route::post('/requests/{id}/deliver', [IdCardRequestController::class, 'deliver']);

            // Settings
            Route::get('/settings', [IdCardSettingController::class, 'show']);
            Route::put('/settings', [IdCardSettingController::class, 'update']);
            Route::apiResource('payment-methods', PaymentMethodController::class);
            Route::apiResource('types', \App\Http\Controllers\Api\Admin\IdCard\IdCardTypeController::class);
            Route::post('/types/{idCardType}/toggle-active', [\App\Http\Controllers\Api\Admin\IdCard\IdCardTypeController::class, 'toggleActive']);
        });
    });

    // Student routes
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/ping', [StudentController::class, 'ping']);
        
        // Announcements
        Route::get('/announcements', [\App\Http\Controllers\Api\Student\AnnouncementController::class, 'index']);

        // Unified My Requests (all modules)
        Route::get('/my-requests', [UnifiedRequestsController::class, 'index']);
    });

    // Transport API routes (student access)
    Route::middleware('role:student')->prefix('transport')->group(function () {
        // Read routes
        Route::get('/routes', [StudentTransportController::class, 'routes']);
        Route::get('/payment-methods', [StudentTransportController::class, 'paymentMethods']);
        Route::get('/settings', [StudentTransportController::class, 'settings']);
        Route::get('/plans', [TransportPlanController::class, 'index']);

        // Subscription request submission (with rate limiting)
        Route::middleware('throttle:uploads')->post('/subscription-requests', [StudentTransportRequestController::class, 'store']);
        Route::post('/subscription-requests/{id}', [StudentTransportRequestController::class, 'update']); // Update/Resubmit

        // Student status endpoints
        Route::get('/my-requests', [StudentTransportStatusController::class, 'myRequests']);
        Route::get('/my-subscription', [StudentTransportStatusController::class, 'mySubscription']);
    });

    // ID Card API routes (student access)
    Route::middleware('role:student')->prefix('id-card')->group(function () {
        // Read routes
        Route::get('/types', [StudentIdCardController::class, 'types']);
        Route::get('/settings', [StudentIdCardController::class, 'settings']);
        Route::get('/payment-methods', [StudentIdCardController::class, 'paymentMethods']);

        // Request submission (with rate limiting)
        Route::middleware('throttle:uploads')->post('/requests', [StudentIdCardRequestController::class, 'store']);
        Route::post('/requests/{id}', [StudentIdCardRequestController::class, 'update']); // Update/Resubmit

        // My requests
        Route::get('/my-requests', [StudentIdCardRequestController::class, 'index']);
        Route::get('/my-requests/{id}', [StudentIdCardRequestController::class, 'show']);
    });
});

