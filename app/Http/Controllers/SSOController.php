<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\TransportLogger;
use App\Support\ApiResponse;

class SSOController extends Controller
{
    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return ApiResponse::error('Token missing', null, 400);
        }

        // SECURITY: Log only token hash, not plain text
        TransportLogger::info('SSO Verification attempting', [
            'token_hash' => substr(hash('sha256', $token), 0, 8),
            'ip' => $request->ip(),
        ]);

        try {
            // SECURITY: SSL verification enabled in production, disabled in local
            $http = config('app.env') === 'local'
                ? Http::withoutVerifying()
                : Http::timeout(30);
                
            $response = $http->timeout(30)->withToken($token)->get('https://batechu.com/api/student/user');

            if ($response->successful()) {
                $data = $response->json();

                // Extract student data from the 'student' key as per provided JSON
                $studentData = $data['student'] ?? null;

                if (!$studentData) {
                    TransportLogger::error('SSO: Student data key not found', ['response' => $data]);
                    return \App\Support\ApiResponse::error('Invalid response from provider');
                }

                $email = $studentData['email'] ?? null;
                $name = $studentData['name'] ?? 'Student';
                $avatar = $studentData['image'] ?? null;

                // Extra Profile Fields
                $academic_id = $studentData['academic_id'] ?? null;
                $national_id = $studentData['national_id'] ?? null;
                $program_name = $studentData['program']['title'] ?? null;
                $level_name = $studentData['year']['name'] ?? null;

                if (!$email) {
                    TransportLogger::error('SSO: Email not found in student data', ['student_data' => $studentData]);
                    return \App\Support\ApiResponse::error('Email not found in provider data');
                }

                // Find or create the user (handle soft-deleted users for SSO compatibility)
                $user = User::withTrashed()->where('email', $email)->first();

                if ($user) {
                    // Restore if soft deleted
                    if ($user->trashed()) {
                        $user->restore();
                        \App\Services\AuditLogger::logRoleAssignment($user->id, [], 'user_restored_via_sso');
                    }
                    // Update user data from SSO
                    $user->update([
                        'name' => $name,
                        'password' => $user->password ?: Hash::make(Str::random(24)),
                        'email_verified_at' => $user->email_verified_at ?: now(),
                        'avatar_url' => $avatar,
                        'academic_id' => $academic_id,
                        'national_id' => $national_id,
                        'program_name' => $program_name,
                        'level_name' => $level_name,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'email' => $email,
                        'name' => $name,
                        'password' => Hash::make(Str::random(24)),
                        'email_verified_at' => now(),
                        'avatar_url' => $avatar,
                        'academic_id' => $academic_id,
                        'national_id' => $national_id,
                        'program_name' => $program_name,
                        'level_name' => $level_name,
                    ]);
                }


                // Update details if changed (Sync always for now to be safe)
                $user->update([
                    'name' => $name,
                    'avatar_url' => $avatar,
                    'academic_id' => $academic_id,
                    'national_id' => $national_id,
                    'program_name' => $program_name,
                    'level_name' => $level_name,
                ]);

            // Enhanced role assignment logic
            // Ensure all users have at least one role
            if (!$user->roles()->exists()) {
                $user->assignRole('student'); // Default role for all new users
                \App\Services\AuditLogger::logRoleAssignment($user->id, ['student'], 'auto_assigned');
            }

            // Auto-assign admin role to configured emails
            $adminEmails = config('auth.admin_emails', []);
            if (!empty($adminEmails) && in_array($email, $adminEmails)) {
                if (!$user->hasRole('admin')) {
                    $user->assignRole('admin');
                    \App\Services\AuditLogger::logRoleAssignment($user->id, ['admin'], 'auto_assigned');
                }
            }

                // SECURITY: Regenerate session after authentication (prevent session fixation)
                session()->regenerate();

                // Create a token for the frontend
                $tokenResult = $user->createToken('sso-token');
                $plainTextToken = $tokenResult->plainTextToken;

                TransportLogger::info('SSO: User authenticated successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return \App\Support\ApiResponse::success([
                    'token' => $plainTextToken,
                    'user' => $user
                ], 'SSO Verification Successful');
            } else {
                TransportLogger::error('SSO: Verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Attempt to decode provider body, fallback to raw body
                try {
                    $providerBody = $response->json();
                } catch (\Exception $e) {
                    $providerBody = $response->body();
                }

                // Return provider status and body to the frontend for better diagnostics
                return \App\Support\ApiResponse::error('Provider verification failed', $providerBody, $response->status());
            }

        } catch (\Exception $e) {
            TransportLogger::error('SSO: Exception occurred', ['message' => $e->getMessage()]);
            return \App\Support\ApiResponse::error('Internal server error during SSO');
        }
    }
}
