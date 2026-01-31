<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdCardRequest;
use App\Models\TransportSubscriptionRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnifiedRequestsController extends Controller
{
    /**
     * Get unified list of all requests for the authenticated student.
     * Merges Transport and ID Card requests, normalized shape, sorted by created_at desc.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Fetch transport requests (last 50)
        $transportRequests = TransportSubscriptionRequest::where('user_id', $userId)
            ->with(['route', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => $this->normalizeTransport($r));

        // Fetch ID card requests (last 50)
        $idCardRequests = IdCardRequest::where('user_id', $userId)
            ->with('type')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => $this->normalizeIdCard($r));

        // Merge, sort by created_at desc, and limit to 50
        $allRequests = $transportRequests->concat($idCardRequests)
            ->sortByDesc('created_at')
            ->take(50)
            ->values();

        return ApiResponse::success($allRequests, 'Requests retrieved successfully');
    }

    /**
     * Normalize a transport subscription request.
     */
    protected function normalizeTransport(TransportSubscriptionRequest $request): array
    {
        $statusLabels = [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        $statusColors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        return [
            'id' => $request->id,
            'module' => 'transport',
            'type_code' => 'subscription',
            'type_label' => $request->route?->name_en ?? 'Transport Subscription',
            'type_label_ar' => $request->route?->name_ar ?? 'اشتراك النقل',
            'status' => $request->status,
            'status_label' => $statusLabels[$request->status] ?? ucfirst($request->status),
            'status_color' => $statusColors[$request->status] ?? 'secondary',
            'amount' => (float) $request->amount_expected,
            'created_at' => $request->created_at->toIso8601String(),
            'detail_url' => '/student/transport/my-requests',
        ];
    }

    /**
     * Normalize an ID card request.
     */
    protected function normalizeIdCard(IdCardRequest $request): array
    {
        return [
            'id' => $request->id,
            'module' => 'id_card',
            'type_code' => $request->type->code,
            'type_label' => $request->type->name_en,
            'type_label_ar' => $request->type->name_ar,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'status_color' => $request->status->color(),
            'amount' => (float) $request->amount_snapshot,
            'created_at' => $request->created_at->toIso8601String(),
            'detail_url' => '/student/id-card/my-requests',
        ];
    }
}
