<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class ThreeTestStudentsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure student role exists
        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $students = [
            [
                'name' => 'Ahmed Mohamed',
                'email' => 'student1@university.edu',
            ],
            [
                'name' => 'Sara Ali',
                'email' => 'student2@university.edu',
            ],
            [
                'name' => 'Omar Khaled',
                'email' => 'student3@university.edu',
            ],
        ];

        foreach ($students as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'), // Simple password for testing
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole('student')) {
                $user->assignRole('student');
            }
            
            $this->command->info("User created: {$user->email} / password");
        }
    }
}
