<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;

class StudentController extends Controller
{
    /**
     * Test endpoint for student role.
     */
    public function ping()
    {
        return ApiResponse::success(['message' => 'Student ping successful']);
    }
}
