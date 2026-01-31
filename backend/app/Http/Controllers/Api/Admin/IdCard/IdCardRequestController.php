<?php

namespace App\Http\Controllers\Api\Admin\IdCard;

use App\Enums\IdCard\PaymentStatus;
use App\Enums\IdCard\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdCard\FlagPaymentRequest;
use App\Http\Requests\IdCard\RejectIdCardRequest;
use App\Http\Resources\Admin\IdCardRequestDetailResource;
use App\Http\Resources\Admin\IdCardRequestResource;
use App\Models\IdCardRequest;
use App\Support\ApiResponse;
use App\Support\IdCardStorage;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdCardRequestController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * List ID card requests with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = IdCardRequest::with(['user', 'type']);

        // Filter by status
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // Filter by type code
        if ($request->filled('type_code')) {
            $query->ofTypeCode($request->type_code);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->paymentStatus($request->payment_status);
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
            'requests' => IdCardRequestResource::collection($requests),
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
    public function show($id): JsonResponse
    {
        $request = IdCardRequest::with([
            'user',
            'type',
            'reviewer',
            'paymentVerifier',
            'deliverer',
        ])->find($id);

        if (!$request) {
            return ApiResponse::error('Request not found', null, 404);
        }

        return ApiResponse::success(new IdCardRequestDetailResource($request));
    }

    /**
     * Download an attachment (screenshot or new_photo).
     */
    public function attachment($id, $kind): JsonResponse|StreamedResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        // Authorization check (defense in depth - route is already admin-only)
        // $this->authorize('viewAttachments', $idCardRequest);

        // Validate kind
        if (!in_array($kind, ['screenshot', 'new_photo'])) {
            return ApiResponse::error('Invalid attachment type', null, 400);
        }

        // Get the path based on kind
        $path = $kind === 'screenshot'
            ? $idCardRequest->transfer_screenshot_path
            : $idCardRequest->new_photo_path;

        if (empty($path)) {
            return ApiResponse::error('Attachment not found', null, 404);
        }

        if (!IdCardStorage::exists($path)) {
            Log::channel('daily')->warning('ID card attachment file missing', [
                'request_id' => $id,
                'kind' => $kind,
                'path' => $path,
            ]);
            return ApiResponse::error('Attachment file not found on disk', null, 404);
        }

        $mimeType = IdCardStorage::mimeType($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = "{$kind}_{$id}.{$extension}";
        Log::info("Downloading attachment: {$fileName} from {$path} (Mime: {$mimeType})");

        return response()->stream(
            function () use ($path) {
                $stream = IdCardStorage::readStream($path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }

    /**
     * Verify payment for a request.
     */
    public function verifyPayment($id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canVerifyPayment()) {
            return ApiResponse::error(
                'Payment cannot be verified in current state',
                ['current_status' => $idCardRequest->status->value],
                409
            );
        }

        // Idempotency: if already verified, just return success
        if ($idCardRequest->payment_status === PaymentStatus::VERIFIED) {
            return ApiResponse::success([
                'id' => $idCardRequest->id,
                'payment_status' => 'verified',
            ], 'Payment already verified');
        }

        $admin = auth()->user();

        $idCardRequest->update([
            'payment_status' => PaymentStatus::VERIFIED->value,
            'payment_flag_reason' => null,
            'payment_verified_at' => now(),
            'payment_verified_by' => $admin->id,
        ]);

        Log::channel('daily')->info('ID card payment verified', [
            'request_id' => $idCardRequest->id,
            'admin_id' => $admin->id,
        ]);

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'payment_status' => 'verified',
            'payment_verified_at' => $idCardRequest->payment_verified_at->toIso8601String(),
        ], 'Payment verified successfully');
    }

    /**
     * Flag payment for a request.
     */
    public function flagPayment(FlagPaymentRequest $formRequest, $id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canFlagPayment()) {
            return ApiResponse::error(
                'Payment cannot be flagged in current state',
                ['current_status' => $idCardRequest->status->value],
                409
            );
        }

        $idCardRequest->update([
            'payment_status' => PaymentStatus::FLAGGED->value,
            'payment_flag_reason' => $formRequest->reason,
            'payment_verified_at' => null,
            'payment_verified_by' => null,
        ]);

        Log::channel('daily')->info('ID card payment flagged', [
            'request_id' => $idCardRequest->id,
            'admin_id' => auth()->id(),
            'reason' => $formRequest->reason,
        ]);



        $this->notificationService->notifyIdCardPaymentFlagged(
            $idCardRequest->user,
            $idCardRequest->id,
            $formRequest->reason
        );

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'payment_status' => 'flagged',
            'payment_flag_reason' => $formRequest->reason,
        ], 'Payment flagged for review');
    }

    /**
     * Approve a pending request.
     */
    public function approve($id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canBeApproved()) {
            // Provide specific error message
            if (!$idCardRequest->status->allowsApproval()) {
                return ApiResponse::error(
                    'Request cannot be approved in current state',
                    ['current_status' => $idCardRequest->status->value],
                    409
                );
            }
            if (!$idCardRequest->isPaymentVerified()) {
                return ApiResponse::error(
                    'Payment must be verified before approval',
                    ['payment_status' => $idCardRequest->payment_status->value],
                    422
                );
            }
        }

        // Idempotency
        if ($idCardRequest->status === RequestStatus::APPROVED) {
            return ApiResponse::success([
                'id' => $idCardRequest->id,
                'status' => 'approved',
            ], 'Request already approved');
        }

        $admin = auth()->user();

        $idCardRequest->update([
            'status' => RequestStatus::APPROVED->value,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        Log::channel('daily')->info('ID card request approved', [
            'request_id' => $idCardRequest->id,
            'admin_id' => $admin->id,
        ]);

        $this->notificationService->notifyIdCardRequestApproved($idCardRequest->user, $idCardRequest->id);

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'status' => 'approved',
            'reviewed_at' => $idCardRequest->reviewed_at->toIso8601String(),
        ], 'Request approved successfully');
    }

    /**
     * Reject a pending request.
     */
    public function reject(RejectIdCardRequest $formRequest, $id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canBeRejected()) {
            return ApiResponse::error(
                'Request cannot be rejected in current state',
                ['current_status' => $idCardRequest->status->value],
                409
            );
        }

        $admin = auth()->user();

        $idCardRequest->update([
            'status' => RequestStatus::REJECTED->value,
            'rejection_reason' => $formRequest->rejection_reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        Log::channel('daily')->info('ID card request rejected', [
            'request_id' => $idCardRequest->id,
            'admin_id' => $admin->id,
            'reason' => $formRequest->rejection_reason,
        ]);

        $this->notificationService->notifyIdCardRequestRejected(
            $idCardRequest->user, 
            $idCardRequest->id,
            $formRequest->rejection_reason
        );

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'status' => 'rejected',
            'rejection_reason' => $formRequest->rejection_reason,
            'reviewed_at' => $idCardRequest->reviewed_at->toIso8601String(),
        ], 'Request rejected');
    }

    /**
     * Mark request as ready for pickup.
     */
    public function ready($id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canBeReadied()) {
            return ApiResponse::error(
                'Request cannot be marked as ready in current state',
                ['current_status' => $idCardRequest->status->value],
                409
            );
        }

        // Idempotency
        if ($idCardRequest->status === RequestStatus::READY_FOR_PICKUP) {
            return ApiResponse::success([
                'id' => $idCardRequest->id,
                'status' => 'ready_for_pickup',
            ], 'Request already marked as ready');
        }

        $idCardRequest->update([
            'status' => RequestStatus::READY_FOR_PICKUP->value,
            'ready_at' => now(),
        ]);

        Log::channel('daily')->info('ID card request marked ready', [
            'request_id' => $idCardRequest->id,
            'admin_id' => auth()->id(),
        ]);

        $this->notificationService->notifyIdCardReadyForPickup($idCardRequest->user, $idCardRequest->id);

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'status' => 'ready_for_pickup',
            'ready_at' => $idCardRequest->ready_at->toIso8601String(),
        ], 'Request marked as ready for pickup');
    }

    /**
     * Mark request as delivered.
     */
    public function deliver($id): JsonResponse
    {
        $idCardRequest = IdCardRequest::find($id);

        if (!$idCardRequest) {
            return ApiResponse::error('Request not found', null, 404);
        }

        if (!$idCardRequest->canBeDelivered()) {
            return ApiResponse::error(
                'Request cannot be marked as delivered in current state',
                ['current_status' => $idCardRequest->status->value],
                409
            );
        }

        // Idempotency
        if ($idCardRequest->status === RequestStatus::DELIVERED) {
            return ApiResponse::success([
                'id' => $idCardRequest->id,
                'status' => 'delivered',
            ], 'Request already delivered');
        }

        $admin = auth()->user();

        $idCardRequest->update([
            'status' => RequestStatus::DELIVERED->value,
            'delivered_at' => now(),
            'delivered_by' => $admin->id,
        ]);

        Log::channel('daily')->info('ID card request delivered', [
            'request_id' => $idCardRequest->id,
            'admin_id' => $admin->id,
        ]);

        $this->notificationService->notifyIdCardDelivered($idCardRequest->user, $idCardRequest->id);

        return ApiResponse::success([
            'id' => $idCardRequest->id,
            'status' => 'delivered',
            'delivered_at' => $idCardRequest->delivered_at->toIso8601String(),
        ], 'Request marked as delivered');
    }

    /**
     * Get dashboard statistics.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'pending' => IdCardRequest::status(RequestStatus::PENDING)->count(),
            'approved' => IdCardRequest::status(RequestStatus::APPROVED)->count(),
            'ready_for_pickup' => IdCardRequest::status(RequestStatus::READY_FOR_PICKUP)->count(),
            'delivered_today' => IdCardRequest::status(RequestStatus::DELIVERED)
                ->whereDate('delivered_at', today())
                ->count(),
            'total_this_month' => IdCardRequest::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'pending_payment_verification' => IdCardRequest::status(RequestStatus::PENDING)
                ->paymentStatus(PaymentStatus::PENDING)
                ->count(),
            'flagged_payments' => IdCardRequest::paymentStatus(PaymentStatus::FLAGGED)->count(),
        ];

        return ApiResponse::success($stats);
    }
}
