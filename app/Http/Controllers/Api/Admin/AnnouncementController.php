<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Announcement;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnnouncementController extends Controller
{
    // protected $notificationService;

    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }

    public function index()
    {
        $announcements = Announcement::latest()->paginate(10);
        return ApiResponse::success($announcements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'send_notification' => 'boolean'
        ]);

        $announcement = Announcement::create($validated);

        if ($request->send_notification) {
            // Notification logic here (placeholder)
        }

        return ApiResponse::success($announcement, 'Announcement created successfully', 201);
    }

    public function show(string $id)
    {
        return ApiResponse::success(Announcement::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:info,warning,danger,success',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($validated);

        return ApiResponse::success($announcement, 'Announcement updated successfully');
    }

    public function destroy(string $id)
    {
        Announcement::destroy($id);
        return ApiResponse::success(null, 'Announcement deleted successfully');
    }
}
