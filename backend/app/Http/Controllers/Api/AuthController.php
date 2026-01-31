<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\TransportLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $request->session()->regenerate();

        TransportLogger::info('User logged in', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
        ]);

        return $this->me($request);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();
        
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
            'role' => $user->getRoleNames()->first() ?? null,
        ]);
    }
}
