<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    /**
     * Display a listing of users with their roles
     */
    public function index()
    {
        $users = User::with('roles')->paginate(50);

        return response()->json($users);
    }

    /**
     * Assign role(s) to a user
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->syncRoles($validated['roles']);

        AuditLogger::logRoleAssignment($user->id, $validated['roles'], 'assigned');

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Remove a specific role from a user
     */
    public function removeRole(User $user, Role $role)
    {
        $user->removeRole($role);

        AuditLogger::logRoleAssignment($user->id, [$role->name], 'removed');

        return response()->json([
            'message' => 'Role removed successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Sync permissions directly to a user
     */
    public function syncPermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Sync direct permissions
        $user->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    /**
     * Get user's permissions breakdown
     */
    public function getUserPermissions(User $user)
    {
        return response()->json([
            'direct_permissions' => $user->permissions->pluck('name'),
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
