<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with counts
     */
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            // Get users count using database query
            $usersCount = DB::table(config('permission.table_names.model_has_roles'))
                ->where('role_id', $role->id)
                ->where('model_type', \App\Models\User::class)
                ->count();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions, // Include full permissions array
                'permissions_count' => $role->permissions->count(),
                'users_count' => $usersCount,
                'created_at' => $role->created_at,
            ];
        });

        return response()->json($roles);
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
        ]);

        AuditLogger::logRoleChange('created', $role);

        return response()->json($role, 201);
    }

    /**
     * Display the specified role with permissions
     */
    public function show(Role $role)
    {
        $role->load('permissions');

        // Get users count
        $usersCount = DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->where('model_type', \App\Models\User::class)
            ->count();

        return response()->json([
            ...$role->toArray(),
            'users_count' => $usersCount,
        ]);
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $role->update($validated);

        AuditLogger::logRoleChange('updated', $role);

        return response()->json($role);
    }

    /**
     * Remove the specified role
     */
    public function destroy(Request $request, Role $role)
    {
        // Protection: Prevent deletion of system roles
        if (in_array($role->name, ['admin', 'student'])) {
            return response()->json([
                'message' => 'Cannot delete system role: ' . $role->name
            ], 403);
        }

        // Protection: Check if role has users assigned
        $usersCount = DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->where('model_type', \App\Models\User::class)
            ->count();

        if ($usersCount > 0 && !$request->has('force')) {
            return response()->json([
                'message' => 'Role has users assigned. Use ?force=1 to detach users and delete.',
                'users_count' => $usersCount
            ], 409);
        }

        DB::transaction(function () use ($role, $request) {
            // Detach users if force flag is set
            if ($request->has('force')) {
                DB::table(config('permission.table_names.model_has_roles'))
                    ->where('role_id', $role->id)
                    ->where('model_type', \App\Models\User::class)
                    ->delete();
            }

            $role->delete();
        });

        // Clear permission cache after transaction commits
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        AuditLogger::logRoleChange('deleted', $role);

        return response()->json(['message' => 'Role deleted successfully']);
    }
}
