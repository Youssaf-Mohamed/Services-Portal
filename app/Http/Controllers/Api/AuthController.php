<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\TransportLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle user login (TEST mode only - disabled in production).
     */
    public function login(Request $request)
    {
        // Disable test login in production environment
        if (app()->environment('production')) {
            abort(404, 'This endpoint is not available in production.');
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return ApiResponse::error('Invalid credentials', null, 401);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        TransportLogger::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? null,
            ]
        ], 'Login successful');
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        TransportLogger::info('User logged out', ['user_id' => $userId]);

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user information.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'academic_id' => $user->academic_id,
            'program_name' => $user->program_name,
            'level_name' => $user->level_name,
            'role' => $user->getRoleNames()->first() ?? null,
            // Enhanced RBAC fields
            'is_admin' => $user->hasRole('admin'),
            'roles' => $user->roles->pluck('name')->values()->all(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->unique()
                ->values()
                ->all()
        ]);
    }
}
