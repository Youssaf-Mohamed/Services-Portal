<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\ApiResponse;

class SecretLoginController extends Controller
{
    /**
     * Handle the secret login request (API).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // 1. Authenticate with External Provider
            // SECURITY: SSL verification enabled
            $response = Http::timeout(30)->post('https://batechu.com/api/user/login', [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if (!$response->successful()) {
                return ApiResponse::error('Invalid credentials or external provider error.', null, 401);
            }

            $loginData = $response->json();
            $token = $loginData['token'] ?? $loginData['data']['token'] ?? $loginData['access_token'] ?? null;

            if (!$token) {
                return ApiResponse::error('Failed to retrieve access token from provider.');
            }

            // 2. Fetch User Details using the Token
            // SECURITY: SSL verification enabled
            $userResponse = Http::timeout(30)
                ->withToken($token)
                ->get('https://batechu.com/api/user/auth-user');

            if (!$userResponse->successful()) {
                return ApiResponse::error('Failed to retrieve user details from provider.');
            }

            $userData = $userResponse->json();
            $userAttributes = $userData['data'] ?? $userData['user'] ?? $userData;

            $email = $userAttributes['email'] ?? null;
            $name = $userAttributes['name'] ?? 'Admin User';
            $avatar = $userAttributes['image'] ?? $userAttributes['avatar'] ?? null;

            if (!$email) {
                return ApiResponse::error('Provider returned invalid user data (no email).');
            }

            // 3. Find or Create Local Admin User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ]
            );

            // Update details
            $user->update([
                'name' => $name,
                'avatar_url' => $avatar,
            ]);

            // Assign Admin Role
            if (method_exists($user, 'hasRole') && !$user->hasRole('admin')) {
                $user->assignRole('admin');
            }

            // SECURITY: Regenerate session after authentication
            session()->regenerate();

            // 4. Create Token for Frontend
            // Since this is a specialized login, we create a Sanctum token.
            $frontendToken = $user->createToken('admin-secret-login')->plainTextToken;

            return ApiResponse::success([
                'token' => $frontendToken,
                'user' => $user
            ], 'Login successful');

        } catch (\Exception $e) {
            return ApiResponse::error('System error: ' . $e->getMessage());
        }
    }
}
