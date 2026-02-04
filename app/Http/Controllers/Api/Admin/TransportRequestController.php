<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveRequestRequest;
use App\Http\Requests\Admin\RejectRequestRequest;
use App\Http\Requests\Admin\BulkApproveRequest;
use App\Http\Requests\Admin\BulkRejectRequest;
use App\Http\Resources\Admin\TransportRequestResource;
use App\Http\Resources\Admin\TransportRequestDetailResource;
use App\Models\TransportSubscriptionRequest;
use App\Models\TransportSubscription;
use App\Models\TransportSeatReservation;
use App\Models\TransportSetting;
use App\Support\ApiResponse;
use App\Support\TransportLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use Carbon\Carbon;

class TransportRequestController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * List subscription requests with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = TransportSubscriptionRequest::with(['user', 'route', 'slot']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by route
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        // Search by student name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by day of week
        if ($request->filled('day_of_week')) {
            $query->whereHas('slot', function ($q) use ($request) {
                $q->where('day_of_week', $request->day_of_week);
            });
        }

        // Filter by time
        if ($request->filled('time')) {
            $query->whereHas('slot', function ($q) use ($request) {
                $q->where('time', $request->time);
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by student name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Order by created_at desc (newest first)
        $query->orderBy('created_at', 'desc');

        $requests = $query->paginate($request->get('per_page', 15));

        return ApiResponse::success([
            'requests' => TransportRequestResource::collection($requests),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Get a single request with full details.
     */
    public function show($id)
    {
        $request = TransportSubscriptionRequest::with([
            'user', 'route.stops', 'slot', 'paymentMethod', 'approver', 'subscription'
        ])->find($id);

        if (!$request) {
            return ApiResponse::error('Request not found', null, 404);
        }

        return ApiResponse::success(new TransportRequestDetailResource($request));
    }

    /**
     * Approve a pending subscription request.
     */
    public function approve(ApproveRequestRequest $formRequest, $id)
    {
        $subscriptionRequest = TransportSubscriptionRequest::with(['slot', 'route'])->find($id);

        if (!$subscriptionRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if ($subscriptionRequest->status !== 'pending') {
            return ApiResponse::error('Request has already been processed', null, 409);
        }

        // Check payment verification
        if ($subscriptionRequest->payment_status !== 'verified') {
            return ApiResponse::error(
                'Payment must be verified before approval. Current status: ' . $subscriptionRequest->payment_status,
                null,
                422
            );
        }

        $admin = auth()->user();
        $startDate = $formRequest->filled('start_date') 
            ? Carbon::parse($formRequest->start_date) 
            : Carbon::today();

        try {
            $result = DB::transaction(function () use ($subscriptionRequest, $startDate, $admin) {
                // Get settings for end_date calculation
                $settings = TransportSetting::first();
                if (!$settings) {
                    throw new \Exception('Transport settings not configured');
                }

                $weeks = $subscriptionRequest->plan_type === 'monthly' 
                    ? $settings->weeks_in_month 
                    : $settings->weeks_in_term;
                $endDate = $startDate->copy()->addDays($weeks * 7 - 1);

                // Check capacity (Only if slot exists)
                $capacityRemaining = null;
                $isWaitlisted = false;
                if ($subscriptionRequest->slot_id && $subscriptionRequest->slot) {
                    $slot = $subscriptionRequest->slot;
                    $activeReservationsCount = TransportSeatReservation::where('slot_id', $slot->id)
                        ->whereNull('released_at')
                        ->count();
                    $capacityRemaining = $slot->capacity - $activeReservationsCount;
                    $isWaitlisted = $capacityRemaining <= 0;
                }

                // Create subscription
                $subscription = TransportSubscription::create([
                    'request_id' => $subscriptionRequest->id,
                    'user_id' => $subscriptionRequest->user_id,
                    'route_id' => $subscriptionRequest->route_id,
                    'slot_id' => $subscriptionRequest->slot_id,
                    'plan_id' => $subscriptionRequest->plan_id,
                    'selected_days' => $subscriptionRequest->selected_days,
                    'plan_type' => $subscriptionRequest->plan_type,
                    'status' => $isWaitlisted ? 'waitlisted' : 'active',
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'amount_paid_expected' => $subscriptionRequest->amount_expected,
                ]);

                // Create seat reservation if capacity available AND slot exists
                if (!$isWaitlisted && $subscription->slot_id) {
                    TransportSeatReservation::create([
                        'slot_id' => $subscription->slot_id,
                        'subscription_id' => $subscription->id,
                        'reserved_at' => now(),
                    ]);
                }

                // Update request status
                $subscriptionRequest->update([
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                ]);

                // Log the action
                TransportLogger::info('Subscription request approved', [
                    'request_id' => $subscriptionRequest->id,
                    'subscription_id' => $subscription->id,
                    'subscription_status' => $subscription->status,
                    'admin_id' => $admin->id,
                    'capacity_remaining' => $capacityRemaining,
                ]);

                return [
                    'subscription' => $subscription,
                    'is_waitlisted' => $isWaitlisted,
                ];
            });

            // Send notification
            if ($result['is_waitlisted']) {
                $this->notificationService->notifyRequestWaitlisted($subscriptionRequest->user);
            } else {
                $this->notificationService->notifyRequestApproved($subscriptionRequest->user, $result['subscription']->id);
            }

            // Reload request with new relationships
            $subscriptionRequest->refresh();
            $subscriptionRequest->load(['user', 'route', 'slot', 'paymentMethod', 'approver', 'subscription']);

            return ApiResponse::success([
                'request' => new TransportRequestDetailResource($subscriptionRequest),
                'subscription_status' => $result['is_waitlisted'] ? 'waitlisted' : 'active',
                'message' => $result['is_waitlisted'] 
                    ? 'Request approved but subscription is waitlisted due to full capacity' 
                    : 'Request approved and subscription activated',
            ]);

        } catch (\Exception $e) {
            TransportLogger::error('Failed to approve request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Failed to approve request: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject a pending subscription request.
     */
    public function reject(RejectRequestRequest $formRequest, $id)
    {
        $subscriptionRequest = TransportSubscriptionRequest::find($id);

        if (!$subscriptionRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if ($subscriptionRequest->status !== 'pending') {
            return ApiResponse::error('Request has already been processed', null, 409);
        }

        $admin = auth()->user();

        $subscriptionRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $formRequest->rejection_reason,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        TransportLogger::info('Subscription request rejected', [
            'request_id' => $subscriptionRequest->id,
            'admin_id' => $admin->id,
            'reason' => $formRequest->rejection_reason,
        ]);

        // Send notification
        $this->notificationService->notifyRequestRejected(
            $subscriptionRequest->user, 
            $formRequest->rejection_reason
        );

        $subscriptionRequest->load(['user', 'route', 'slot', 'paymentMethod', 'approver']);

        return ApiResponse::success([
            'request' => new TransportRequestDetailResource($subscriptionRequest),
            'message' => 'Request rejected successfully',
        ]);
    }

    /**
     * Bulk approve multiple requests.
     */
    public function bulkApprove(BulkApproveRequest $request)
    {
        $ids = $request->ids;
        $admin = auth()->user();
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::today();

        $processed = 0;
        $failed = 0;
        $results = [];

        try {
            DB::transaction(function () use ($ids, $admin, $startDate, &$processed, &$failed, &$results) {
                $requests = TransportSubscriptionRequest::with(['slot', 'route'])
                    ->whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->get();

                // Get settings once
                $settings = TransportSetting::first();
                if (!$settings) throw new \Exception('Transport settings not configured');

                foreach ($requests as $subscriptionRequest) {
                    try {
                        // Calculate End Date
                        $weeks = $subscriptionRequest->plan_type === 'monthly' 
                            ? $settings->weeks_in_month 
                            : $settings->weeks_in_term;
                        $endDate = $startDate->copy()->addDays($weeks * 7 - 1);

                        // Check capacity (Only if slot exists)
                        $isWaitlisted = false;
                        if ($subscriptionRequest->slot_id && $subscriptionRequest->slot) {
                            $slot = $subscriptionRequest->slot;
                            $activeReservationsCount = TransportSeatReservation::where('slot_id', $slot->id)
                                ->whereNull('released_at')
                                ->count();
                            $capacityRemaining = $slot->capacity - $activeReservationsCount;
                            $isWaitlisted = $capacityRemaining <= 0;
                        }

                        // Create subscription
                        $subscription = TransportSubscription::create([
                            'request_id' => $subscriptionRequest->id,
                            'user_id' => $subscriptionRequest->user_id,
                            'route_id' => $subscriptionRequest->route_id,
                            'slot_id' => $subscriptionRequest->slot_id,
                            'plan_id' => $subscriptionRequest->plan_id,
                            'selected_days' => $subscriptionRequest->selected_days,
                            'plan_type' => $subscriptionRequest->plan_type,
                            'status' => $isWaitlisted ? 'waitlisted' : 'active',
                            'starts_at' => $startDate,
                            'ends_at' => $endDate,
                            'amount_paid_expected' => $subscriptionRequest->amount_expected,
                        ]);

                        // Reserve seat if available AND slot exists
                        if (!$isWaitlisted && $subscription->slot_id) {
                            TransportSeatReservation::create([
                                'slot_id' => $subscription->slot_id,
                                'subscription_id' => $subscription->id,
                                'reserved_at' => now(),
                            ]);
                        }

                        // Update request
                        $subscriptionRequest->update([
                            'status' => 'approved',
                            'approved_by' => $admin->id,
                            'approved_at' => now(),
                        ]);

                        $processed++;
                        $results[] = [
                            'id' => $subscriptionRequest->id,
                            'status' => 'success',
                            'subscription_status' => $isWaitlisted ? 'waitlisted' : 'active'
                        ];
                    } catch (\Exception $e) {
                         $failed++;
                         $results[] = ['id' => $subscriptionRequest->id, 'status' => 'failed', 'error' => $e->getMessage()];
                    }
                }
            });

            return ApiResponse::success([
                'message' => "Processed {$processed} requests successfully. Failed: {$failed}.",
                'results' => $results,
            ]);

        } catch (\Exception $e) {
             return ApiResponse::error('Bulk approval failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk reject multiple requests.
     */
    public function bulkReject(BulkRejectRequest $request)
    {
        $ids = $request->ids;
        $admin = auth()->user();

        $count = TransportSubscriptionRequest::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

        return ApiResponse::success([
            'message' => "Rejected {$count} requests successfully.",
        ]);
    }

    /**
     * Download proof file securely.
     */
    public function downloadProof($id)
    {
        $subscriptionRequest = TransportSubscriptionRequest::find($id);

        if (!$subscriptionRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$subscriptionRequest->proof_path) {
            return ApiResponse::error('No proof file attached', null, 404);
        }

        if (!Storage::disk('proofs')->exists($subscriptionRequest->proof_path)) {
            return ApiResponse::error('Proof file not found on disk', null, 404);
        }

        $mimeType = Storage::disk('proofs')->mimeType($subscriptionRequest->proof_path);
        $fileName = 'proof_' . $subscriptionRequest->id . '.' . pathinfo($subscriptionRequest->proof_path, PATHINFO_EXTENSION);

        return response()->stream(
            function () use ($subscriptionRequest) {
                $stream = Storage::disk('proofs')->readStream($subscriptionRequest->proof_path);
                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }

    /**
     * Verify payment for a subscription request.
     */
    public function verifyPayment($id)
    {
        $subscriptionRequest = TransportSubscriptionRequest::find($id);

        if (!$subscriptionRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if ($subscriptionRequest->status !== 'pending') {
            return ApiResponse::error('Only pending requests can have payment verified', null, 409);
        }

        if ($subscriptionRequest->payment_status === 'verified') {
            return ApiResponse::error('Payment is already verified', null, 409);
        }

        $admin = auth()->user();

        $subscriptionRequest->update([
            'payment_status' => 'verified',
            'payment_flag_reason' => null,
            'payment_verified_at' => now(),
            'payment_verified_by' => $admin->id,
        ]);

        TransportLogger::log('payment_verified', [
            'request_id' => $subscriptionRequest->id,
            'student_id' => $subscriptionRequest->user_id,
        ]);

        try {
            $this->notificationService->notifyTransportPaymentVerified(
                $subscriptionRequest->user,
                $subscriptionRequest->id
            );
        } catch (\Exception $e) {
            // Don't fail the verification if notification fails
            TransportLogger::error('Failed to send payment verification notification', ['error' => $e->getMessage()]);
        }

        return ApiResponse::success([
            'id' => $subscriptionRequest->id,
            'payment_status' => 'verified',
            'payment_verified_at' => $subscriptionRequest->payment_verified_at->toIso8601String(),
        ], 'Payment verified successfully');
    }

    /**
     * Flag payment for a subscription request (requires review).
     */
    public function flagPayment(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $subscriptionRequest = TransportSubscriptionRequest::find($id);

        if (!$subscriptionRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if ($subscriptionRequest->status !== 'pending') {
            return ApiResponse::error('Only pending requests can be flagged', null, 409);
        }

        $subscriptionRequest->update([
            'payment_status' => 'flagged',
            'payment_flag_reason' => $request->reason,
            'payment_verified_at' => null,
            'payment_verified_by' => null,
        ]);

        TransportLogger::log('payment_flagged', [
            'request_id' => $subscriptionRequest->id,
            'student_id' => $subscriptionRequest->user_id,
            'reason' => $request->reason,
        ]);

        $this->notificationService->notifyTransportPaymentFlagged(
            $subscriptionRequest->user,
            $subscriptionRequest->id,
            $request->reason
        );

        return ApiResponse::success([
            'id' => $subscriptionRequest->id,
            'payment_status' => 'flagged',
            'payment_flag_reason' => $request->reason,
        ], 'Payment flagged for review');
    }
}
