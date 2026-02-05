<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class TransportLogger
{
    /**
     * Log a transport-related admin action.
     *
     * @param string $action The action being logged
     * @param array $context Additional context data
     */
    public static function log(string $action, array $context = []): void
    {
        Log::channel('transport')->info($action, array_merge([
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Log a transport-related error.
     *
     * @param string $message The error message
     * @param array $context Additional context data
     */
    public static function error(string $message, array $context = []): void
    {
        Log::channel('transport')->error($message, array_merge([
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Alias for log() - for compatibility with Laravel Log facade.
     */
    public static function info(string $action, array $context = []): void
    {
        self::log($action, $context);
    }

    /**
     * Log a warning message.
     */
    public static function warning(string $message, array $context = []): void
    {
        Log::channel('transport')->warning($message, array_merge([
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Log a payment verification action.
     */
    public static function logPaymentVerified(int $requestId, int $studentId): void
    {
        self::log('payment_verified', [
            'request_id' => $requestId,
            'student_id' => $studentId,
        ]);
    }

    /**
     * Log a payment flagged action.
     */
    public static function logPaymentFlagged(int $requestId, int $studentId, string $reason): void
    {
        self::log('payment_flagged', [
            'request_id' => $requestId,
            'student_id' => $studentId,
            'reason' => $reason,
        ]);
    }

    /**
     * Log a request approval action.
     */
    public static function logApproval(int $requestId, int $studentId, int $subscriptionId): void
    {
        self::log('request_approved', [
            'request_id' => $requestId,
            'student_id' => $studentId,
            'subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * Log a request rejection action.
     */
    public static function logRejection(int $requestId, int $studentId, string $reason): void
    {
        self::log('request_rejected', [
            'request_id' => $requestId,
            'student_id' => $studentId,
            'reason' => $reason,
        ]);
    }
}
