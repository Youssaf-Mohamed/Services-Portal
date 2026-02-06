<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class VerifyUserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'youssafmohamed2002@gmail.com';
        
        $user = User::where('email', $adminEmail)->first();
        
        if (!$user) {
            echo "❌ User not found: {$adminEmail}\n";
            return;
        }
        
        echo "✅ User found: {$user->name}\n";
        echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";
        
        echo "=== CHECKING PERMISSIONS ===\n";
        $permissions = $user->getAllPermissions();
        echo "Total permissions: " . $permissions->count() . "\n\n";
        
        $requiredPermissions = [
            'system.admin.access',
            'system.roles.manage',
            'system.permissions.manage',
            'system.users.manage'
        ];
        
        foreach ($requiredPermissions as $perm) {
            $has = $user->can($perm);
            echo ($has ? "✅" : "❌") . " {$perm}\n";
        }
        
        echo "\n=== ALL PERMISSIONS ===\n";
        foreach ($permissions as $p) {
            echo "- {$p->name}\n";
        }
    }
}
