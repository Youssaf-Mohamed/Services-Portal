<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LmsIntegrationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get unread notification count for a specific student.
     * Expects 'email' or 'national_id' query parameter.
     */
    public function getNotificationCount(Request $request)
    {
        $request->validate([
            'email' => 'required_without:national_id|email',
            'national_id' => 'required_without:email|string',
        ]);

        $query = User::query();

        if ($request->has('email')) {
            $query->where('email', $request->email);
        } else {
            $query->where('national_id', $request->national_id);
        }

        $user = $query->first();

        if (!$user) {
            return ApiResponse::error('User not found', null, 404);
        }

        try {
            $count = $this->notificationService->getUnreadCount($user);
            return ApiResponse::success(['count' => $count, 'user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('LMS Integration Error: ' . $e->getMessage());
            return ApiResponse::error('Internal Server Error', null, 500);
        }
    }
}
