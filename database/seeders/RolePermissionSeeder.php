<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions with module, description, and sort_order
        $permissions = [
            // System Module
            ['name' => 'system.admin.access', 'module' => 'system', 'description' => 'Access admin panel', 'sort_order' => 1],
            ['name' => 'system.roles.manage', 'module' => 'system', 'description' => 'Create, edit, delete roles', 'sort_order' => 2],
            ['name' => 'system.permissions.manage', 'module' => 'system', 'description' => 'Create, edit, delete permissions', 'sort_order' => 3],
            ['name' => 'system.users.manage', 'module' => 'system', 'description' => 'View users and assign roles', 'sort_order' => 4],
            ['name' => 'system.data.purge', 'module' => 'system', 'description' => 'Permanently delete soft-deleted records', 'sort_order' => 5],

            // Transport Module
            ['name' => 'transport.admin.access', 'module' => 'transport', 'description' => 'Access transport admin section', 'sort_order' => 1],
            ['name' => 'transport.dashboard.view', 'module' => 'transport', 'description' => 'View transport statistics', 'sort_order' => 2],
            ['name' => 'transport.requests.manage', 'module' => 'transport', 'description' => 'Approve/reject transport requests', 'sort_order' => 3],
            ['name' => 'transport.routes.manage', 'module' => 'transport', 'description' => 'CRUD bus routes', 'sort_order' => 4],
            ['name' => 'transport.slots.manage', 'module' => 'transport', 'description' => 'CRUD schedule slots', 'sort_order' => 5],
            ['name' => 'transport.stops.manage', 'module' => 'transport', 'description' => 'CRUD bus stops', 'sort_order' => 6],
            ['name' => 'transport.reports.view', 'module' => 'transport', 'description' => 'Access transport reports/manifest', 'sort_order' => 7],
            ['name' => 'transport.settings.manage', 'module' => 'transport', 'description' => 'Edit transport system settings', 'sort_order' => 8],
            ['name' => 'transport.requests.delete', 'module' => 'transport', 'description' => 'Delete/restore transport requests', 'sort_order' => 9],
            ['name' => 'transport.subscriptions.delete', 'module' => 'transport', 'description' => 'Cancel/restore transport subscriptions', 'sort_order' => 10],

            // ID Card Module
            ['name' => 'idcard.admin.access', 'module' => 'idcard', 'description' => 'Access ID card admin section', 'sort_order' => 1],
            ['name' => 'idcard.dashboard.view', 'module' => 'idcard', 'description' => 'View ID card statistics', 'sort_order' => 2],
            ['name' => 'idcard.requests.manage', 'module' => 'idcard', 'description' => 'Approve/reject ID card requests', 'sort_order' => 3],
            ['name' => 'idcard.types.manage', 'module' => 'idcard', 'description' => 'CRUD ID card types', 'sort_order' => 4],
            ['name' => 'idcard.settings.manage', 'module' => 'idcard', 'description' => 'Edit ID card system settings', 'sort_order' => 5],
            ['name' => 'idcard.requests.delete', 'module' => 'idcard', 'description' => 'Delete/restore ID card requests', 'sort_order' => 6],

            // Announcements Module
            ['name' => 'announcements.view', 'module' => 'announcements', 'description' => 'View announcements (student permission)', 'sort_order' => 1],
            ['name' => 'announcements.manage', 'module' => 'announcements', 'description' => 'Create, edit, delete announcements', 'sort_order' => 2],
        ];

        // Create permissions (idempotent)
        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name'], 'guard_name' => 'web'],
                [
                    'module' => $permData['module'],
                    'description' => $permData['description'],
                    'sort_order' => $permData['sort_order']
                ]
            );
        }

        // Create roles (idempotent)
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['description' => 'System Administrator - Full access to all features']
        );

        $studentRole = Role::firstOrCreate(
            ['name' => 'student', 'guard_name' => 'web'],
            ['description' => 'Regular Student - View-only access']
        );

        $transportManagerRole = Role::firstOrCreate(
            ['name' => 'transport_manager', 'guard_name' => 'web'],
            ['description' => 'Transport Department Manager - Manages transport services']
        );

        $idCardOfficerRole = Role::firstOrCreate(
            ['name' => 'id_card_officer', 'guard_name' => 'web'],
            ['description' => 'ID Card Officer - Manages ID card requests']
        );

        // Assign permissions to roles (idempotent using syncPermissions)
        
        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());

        // Student gets limited permissions
        $studentRole->syncPermissions([
            'announcements.view'
        ]);

        // Transport Manager gets transport + system admin access
        $transportManagerRole->syncPermissions([
            'system.admin.access',
            'transport.admin.access',
            'transport.dashboard.view',
            'transport.requests.manage',
            'transport.routes.manage',
            'transport.slots.manage',
            'transport.stops.manage',
            'transport.reports.view',
            'transport.settings.manage',
        ]);

        // ID Card Officer gets ID card + system admin access
        $idCardOfficerRole->syncPermissions([
            'system.admin.access',
            'idcard.admin.access',
            'idcard.dashboard.view',
            'idcard.requests.manage',
            'idcard.types.manage',
            'idcard.settings.manage',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
