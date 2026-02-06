<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    /**
     * Sync permissions for a role (recommended approach)
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // Get before state for audit
        $before = $role->permissions->pluck('name')->toArray();

        // Wrap in transaction
        DB::transaction(function () use ($role, $validated) {
            $role->syncPermissions($validated['permissions']);
        });

        // Clear cache after commit
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Get after state for audit
        $after = $role->fresh()->permissions->pluck('name')->toArray();

        AuditLogger::logRoleChange('permissions_synced', $role, [
            'before' => $before,
            'after' => $after
        ]);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Add permissions to a role (additive)
     */
    public function addPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->givePermissionTo($validated['permissions']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        AuditLogger::logRoleChange('permissions_added', $role, [
            'added' => $validated['permissions']
        ]);

        return response()->json([
            'message' => 'Permissions added successfully',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Remove specific permissions from a role
     */
    public function removePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->revokePermissionTo($validated['permissions']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        AuditLogger::logRoleChange('permissions_removed', $role, [
            'removed' => $validated['permissions']
        ]);

        return response()->json([
            'message' => 'Permissions removed successfully',
            'role' => $role->load('permissions')
        ]);
    }
}
