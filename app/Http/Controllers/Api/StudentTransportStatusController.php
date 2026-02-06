<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentSubscriptionRequestResource;
use App\Http\Resources\StudentSubscriptionResource;
use App\Models\TransportSubscription;
use App\Models\TransportSubscriptionRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentTransportStatusController extends Controller
{
    /**
     * Get all subscription requests for the authenticated student.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $requests = TransportSubscriptionRequest::where('user_id', $request->user()->id)
            ->with([
                'route' => function ($query) {
                    $query->select('id', 'name_ar', 'name_en', 'price_one_way', 'monthly_discount_percent', 'term_discount_percent');
                },
                'slot' => function ($query) {
                    $query->select('id', 'day_of_week', 'time', 'direction');
                },
                'paymentMethod' => function ($query) {
                    $query->select('id', 'name');
                },
                'subscription' => function ($query) {
                    $query->select('id', 'request_id', 'status');
                },
                'plan' => function ($query) {
                    $query->select('id', 'name_ar', 'name_en');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->simplePaginate(10);

        return ApiResponse::success(
            StudentSubscriptionRequestResource::collection($requests),
            'OK'
        );
    }

    /**
     * Get the active/waitlisted subscription for the authenticated student.
     */
    public function mySubscription(Request $request): JsonResponse
    {
        // Prefer active, else waitlisted
        $subscription = TransportSubscription::where('user_id', $request->user()->id)
            ->whereIn('status', ['active', 'waitlisted'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'waitlisted' THEN 1 ELSE 2 END")
            ->with([
                'route' => function ($query) {
                    $query->select('id', 'name_ar', 'name_en');
                },
                'slot' => function ($query) {
                    $query->select('id', 'day_of_week', 'time', 'direction');
                },
                'request' => function ($query) {
                    $query->select('id', 'pricing_snapshot', 'plan_type');
                },
                'plan' => function ($query) {
                    $query->select('id', 'name_ar', 'name_en');
                },
                'reservation',
            ])
            ->first();

        if (!$subscription) {
            return ApiResponse::success(null, 'No active subscription');
        }

        return ApiResponse::success(
            new StudentSubscriptionResource($subscription),
            'OK'
        );
    }
}
