<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder is idempotent - can be run multiple times safely.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Password123!'),
            ]
        );
        
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create student user
        $student = User::firstOrCreate(
            ['email' => 'student@test.com'],
            [
                'name' => 'Student User',
                'password' => Hash::make('Password123!'),
            ]
        );
        
        if (!$student->hasRole('student')) {
            $student->assignRole('student');
        }

        $this->command->info('Test users created successfully!');
        $this->command->info('Admin: admin@test.com / Password123!');
        $this->command->info('Student: student@test.com / Password123!');
    }
}
