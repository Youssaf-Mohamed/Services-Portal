<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Audit Logger Service
 * Logs security-critical events for compliance and monitoring
 */
class AuditLogger
{
    /**
     * Log a successful authentication
     */
    public static function logLogin(string $email, string $ip): void
    {
        Log::channel('security')->info('User login successful', [
            'event' => 'auth.login',
            'email' => $email,
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log a failed authentication attempt
     */
    public static function logFailedLogin(string $email, string $ip, string $reason = 'Invalid credentials'): void
    {
        Log::channel('security')->warning('Failed login attempt', [
            'event' => 'auth.login_failed',
            'email' => $email,
            'ip' => $ip,
            'reason' => $reason,
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log a logout event
     */
    public static function logLogout(?int $userId = null): void
    {
        Log::channel('security')->info('User logout', [
            'event' => 'auth.logout',
            'user_id' => $userId ?? Auth::id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess(string $resource, ?int $userId = null): void
    {
        Log::channel('security')->warning('Unauthorized access attempt', [
            'event' => 'access.unauthorized',
            'user_id' => $userId ?? Auth::id(),
            'resource' => $resource,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log permission denial
     */
    public static function logPermissionDenied(string $permission, ?int $userId = null): void
    {
        Log::channel('security')->warning('Permission denied', [
            'event' => 'permission.denied',
            'user_id' => $userId ?? Auth::id(),
            'permission' => $permission,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log data export (sensitive operation)
     */
    public static function logDataExport(string $exportType, array $criteria,int $recordCount): void
    {
        Log::channel('audit')->info('Data export performed', [
            'event' => 'data.export',
            'user_id' => Auth::id(),
            'export_type' => $exportType,
            'criteria' => $criteria,
            'record_count' => $recordCount,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log admin action (approvals, rejections, etc.)
     */
    public static function logAdminAction(string $action, string $targetType, int $targetId, array $details = []): void
    {
        Log::channel('audit')->info('Admin action performed', [
            'event' => 'admin.action',
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log critical database operation
     */
    public static function logCriticalOperation(string $operation, array $context = []): void
    {
        Log::channel('audit')->warning('Critical operation executed', [
            'event' => 'system.critical',
            'user_id' => Auth::id(),
            'operation' => $operation,
            'context' => $context,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log security-relevant configuration change
     */
    public static function logConfigChange(string $configKey, $oldValue, $newValue): void
    {
        Log::channel('audit')->warning('Configuration changed', [
            'event' => 'config.change',
            'user_id' => Auth::id(),
            'config_key' => $configKey,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
