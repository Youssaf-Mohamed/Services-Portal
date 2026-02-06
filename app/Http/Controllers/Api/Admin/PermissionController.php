<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions grouped by module
     */
    public function index()
    {
        $permissions = Permission::orderBy('module')->orderBy('sort_order')->get();

        // Group by module
        $grouped = $permissions->groupBy('module')->map(function($group) {
            return $group->map(function($permission) {
                // Check if permission is assigned to any roles
                $rolesCount = DB::table(config('permission.table_names.role_has_permissions'))
                    ->where('permission_id', $permission->id)
                    ->count();

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'description' => $permission->description,
                    'sort_order' => $permission->sort_order,
                    'roles_count' => $rolesCount,
                    'created_at' => $permission->created_at,
                ];
            });
        });

        return response()->json($grouped);
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'module' => 'required|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0|max:255',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'module' => $validated['module'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        AuditLogger::logPermissionChange('created', $permission);

        return response()->json($permission, 201);
    }

    /**
     * Display the specified permission
     */
    public function show(Permission $permission)
    {
        // Load roles that have this permission
        $permission->load('roles');

        return response()->json($permission);
    }

    /**
     * Update the specified permission
     * NOTE: name field is IMMUTABLE
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            // name is intentionally excluded (immutable)
            'module' => 'sometimes|required|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0|max:255',
        ]);

        $permission->update($validated);

        AuditLogger::logPermissionChange('updated', $permission);

        return response()->json($permission);
    }

    /**
     * Remove the specified permission
     */
    public function destroy(Permission $permission)
    {
        // Protection: Check if permission is assigned to any roles
        $rolesCount = DB::table(config('permission.table_names.role_has_permissions'))
            ->where('permission_id', $permission->id)
            ->count();

        if ($rolesCount > 0) {
            return response()->json([
                'message' => 'Permission is assigned to ' . $rolesCount . ' role(s). Remove from roles first.',
                'roles_count' => $rolesCount
            ], 409);
        }

        DB::transaction(function () use ($permission) {
            $permission->delete();
        });

        // Clear permission cache after transaction commits
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        AuditLogger::logPermissionChange('deleted', $permission);

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
