<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->simplePaginate(20);

        return ApiResponse::success([
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'link' => $n->link,
                'read' => $n->isRead(),
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'has_more' => $notifications->hasMorePages(),
        ]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return ApiResponse::success(['count' => $count]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)->find($id);

        if (!$notification) {
            return ApiResponse::error('Notification not found', null, 404);
        }

        $notification->markAsRead();

        return ApiResponse::success(['id' => $id, 'read' => true], 'Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return ApiResponse::success(['marked_count' => $count], 'All notifications marked as read');
    }
}
