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

                // Find or create the user
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make(Str::random(24)),
                        'email_verified_at' => now(),
                    ]
                );

                // Update details if changed (Sync always for now to be safe)
                $user->update([
                    'name' => $name,
                    'avatar_url' => $avatar,
                    'academic_id' => $academic_id,
                    'national_id' => $national_id,
                    'program_name' => $program_name,
                    'level_name' => $level_name,
                ]);

                // Ensure the user has the 'student' role
                if (method_exists($user, 'hasRole') && !$user->hasRole('student')) {
                    $user->assignRole('student');
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
