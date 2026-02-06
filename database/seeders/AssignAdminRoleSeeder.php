<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'youssafmohamed2002@gmail.com';
        
        $user = User::where('email', $adminEmail)->first();
        
        if (!$user) {
            echo "User not found: {$adminEmail}\n";
            return;
        }
        
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            echo "Admin role not found!\n";
            return;
        }
        
        // Assign admin role
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
            echo "✅ Admin role assigned to: {$user->name} ({$user->email})\n";
        } else {
            echo "✅ User already has admin role: {$user->name}\n";
        }
        
        // Show current roles and permissions
        echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
        echo "Permissions count: " . $user->getAllPermissions()->count() . "\n";
    }
}
