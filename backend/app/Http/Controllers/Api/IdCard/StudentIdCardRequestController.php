<?php

namespace App\Http\Controllers\Api\IdCard;

use App\Enums\IdCard\PaymentStatus;
use App\Enums\IdCard\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdCard\StoreIdCardRequestRequest;
use App\Http\Resources\StudentIdCardRequestResource;
use App\Models\IdCardRequest;
use App\Support\ApiResponse;
use App\Support\IdCardStorage;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentIdCardRequestController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List all ID card requests for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $requests = IdCardRequest::where('user_id', $request->user()->id)
            ->with('type')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return ApiResponse::success(
            StudentIdCardRequestResource::collection($requests),
            'Requests retrieved successfully'
        );
    }

    /**
     * Get a single ID card request for the authenticated student.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $idCardRequest = IdCardRequest::where('user_id', $request->user()->id)
            ->with('type')
            ->find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        return ApiResponse::success(
            new StudentIdCardRequestResource($idCardRequest),
            'Request retrieved successfully'
        );
    }

    /**
     * Store a newly created ID card request.
     */
    public function store(StoreIdCardRequestRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->getResolvedType();

        if (!$type) {
            return ApiResponse::error('Invalid service type', null, 422);
        }

        try {
            $idCardRequest = DB::transaction(function () use ($request, $user, $type) {
                // Store the transfer screenshot
                $screenshotPath = IdCardStorage::storeScreenshot(
                    $request->file('transfer_screenshot'),
                    $user->id
                );

                // Store the new photo if provided
                $newPhotoPath = null;
                if ($request->hasFile('new_photo')) {
                    $newPhotoPath = IdCardStorage::storeNewPhoto(
                        $request->file('new_photo'),
                        $user->id
                    );
                }

                // Create the request with fee snapshot
                return IdCardRequest::create([
                    'user_id' => $user->id,
                    'type_id' => $type->id,
                    'status' => RequestStatus::PENDING->value,
                    'amount_snapshot' => $type->fee,
                    'transaction_number' => $request->transaction_number,
                    'transfer_time' => $request->transfer_time,
                    'transfer_screenshot_path' => $screenshotPath,
                    'new_photo_path' => $newPhotoPath,
                    'issue_description' => $request->issue_description,
                    'payment_status' => PaymentStatus::PENDING->value,
                ]);
            });

            // Log the creation
            Log::channel('daily')->info('ID card request submitted', [
                'user_id' => $user->id,
                'request_id' => $idCardRequest->id,
                'type_code' => $type->code,
                'amount' => $type->fee,
            ]);

            // Load type for response
            $idCardRequest->load('type');

            // Send notification
            $this->notificationService->notifyIdCardRequestSubmitted($user, $idCardRequest->id);

            return ApiResponse::success(
                new StudentIdCardRequestResource($idCardRequest),
                'Request submitted successfully',
                201
            );

        } catch (\Exception $e) {
            Log::channel('daily')->error('Failed to create ID card request', [
                'user_id' => $user->id,
                'type_code' => $type->code,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(
                'Failed to submit request. Please try again.',
                null,
                500
            );
        }
    }
}
