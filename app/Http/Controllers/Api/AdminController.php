<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Test endpoint for admin role.
     */
    public function ping()
    {
        return ApiResponse::success(['message' => 'Admin ping successful']);
    }

    /**
     * Check storage configuration for proofs directory.
     */
    public function storageCheck()
    {
        $proofsExists = Storage::disk('public')->exists('proofs');
        
        if (!$proofsExists) {
            Storage::disk('public')->makeDirectory('proofs');
        }

        return ApiResponse::success([
            'proofs_directory_exists' => Storage::disk('public')->exists('proofs'),
            'public_url_base' => config('app.url') . '/storage',
            'storage_path' => storage_path('app/public'),
        ]);
    }
}
