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
use Illuminate\Support\Facades\Storage;

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
                    'paid_from_number' => $request->paid_from_number,
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
    /**
     * Update and resubmit a rejected ID card request.
     */
    /**
     * Update and resubmit a rejected ID card request.
     */
    public function update(\App\Http\Requests\IdCard\UpdateIdCardRequestRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        Log::info("Update Method Reached. Request ID provided: $id");

        $type = $request->getResolvedType();

        if (!$type) {
            Log::warning("Type resolved to null");
            return ApiResponse::error('Invalid service type', null, 422);
        }
        
        $idCardRequest = IdCardRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->first();
            
        if (!$idCardRequest) {
            Log::warning("IdCardRequest not found for user {$user->id} and id $id");
            return ApiResponse::error('Request not found', null, 404);
        }

        Log::info("Request found. Status: {$idCardRequest->status->value}, Payment: {$idCardRequest->payment_status->value}");
        
        // Allow updating if status is REJECTED or payment_status is FLAGGED
        $canUpdate = $idCardRequest->status === RequestStatus::REJECTED || 
                     $idCardRequest->payment_status === PaymentStatus::FLAGGED;

        if (!$canUpdate) {
            Log::warning("Cannot update. conditions failed.");
            return ApiResponse::error('Only rejected requests or requests with flagged payments can be resubmitted.', null, 422);
        }

        try {
            Log::info("Starting transaction...");
            DB::transaction(function () use ($request, $user, $type, $idCardRequest) {
                // Update screenshot if provided
                $screenshotPath = $idCardRequest->transfer_screenshot_path;
                if ($request->hasFile('transfer_screenshot')) {
                    Log::info("Processing new screenshot file...");
                    $screenshotPath = IdCardStorage::storeScreenshot(
                        $request->file('transfer_screenshot'),
                        $user->id
                    );
                }

                // Update new photo if provided
                $newPhotoPath = $idCardRequest->new_photo_path;
                if ($request->hasFile('new_photo')) {
                    Log::info("Processing new photo file...");
                    $newPhotoPath = IdCardStorage::storeNewPhoto(
                        $request->file('new_photo'),
                        $user->id
                    );
                }

                Log::info("Updating database record...");
                // Update the request
                $idCardRequest->update([
                    'type_id' => $type->id,
                    'status' => RequestStatus::PENDING->value,
                    'amount_snapshot' => $type->fee, // Update fee in case type changed
                    'transaction_number' => $request->transaction_number,
                    'paid_from_number' => $request->paid_from_number,
                    'transfer_time' => $request->transfer_time,
                    'transfer_screenshot_path' => $screenshotPath,
                    'new_photo_path' => $newPhotoPath,
                    'issue_description' => $request->issue_description,
                    'payment_status' => PaymentStatus::PENDING->value, // Reset payment verification if changed
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'payment_flag_reason' => null, // Clear flag reason
                ]);
            });

            Log::info("Transaction committed.");

            // Load type for response
            $idCardRequest->load('type');

            return ApiResponse::success(
                new StudentIdCardRequestResource($idCardRequest),
                'Request resubmitted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Failed to update ID card request: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return ApiResponse::error(
                'Failed to resubmit request. Please try again.',
                null,
                500
            );
        }
    }
    /**
     * Download attachment (transfer screenshot or new photo).
     */
    public function downloadAttachment($id, $kind)
    {
        $user = auth()->user();
        $request = IdCardRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $path = match ($kind) {
            'proof', 'transfer_screenshot' => $request->transfer_screenshot_path,
            'photo', 'new_photo' => $request->new_photo_path,
            default => null,
        };

        if (!$path || !Storage::disk('proofs')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('proofs')->download($path, $kind . '_' . $request->id . '.' . pathinfo($path, PATHINFO_EXTENSION));
    }
}
