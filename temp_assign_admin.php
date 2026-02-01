<?php
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

echo "Starting admin assignment...\n";

try {
    // Ensure admin role exists
    Role::firstOrCreate(['name' => 'admin']);

    $emails = ['Youssafmohamed2002@gmail.com', 'omeralgamel@gmail.com'];

    foreach ($emails as $email) {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin User',
                'password' => bcrypt(Str::random(16)), // Only used if creating new user
                'email_verified_at' => now()
            ]
        );

        $user->assignRole('admin');
        echo "Successfully assigned 'admin' role to: $email\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Completed.\n";
