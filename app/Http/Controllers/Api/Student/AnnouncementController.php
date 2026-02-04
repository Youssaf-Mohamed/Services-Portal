<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnnouncementController extends Controller
{
    public function index()
    {
        // Fetch active announcements, ordered by newest
        $announcements = Announcement::active()
            ->latest()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }
}
