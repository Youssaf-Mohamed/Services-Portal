<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportPlan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TransportPlanController extends Controller
{
    /**
     * List all active transport plans.
     */
    public function index(): JsonResponse
    {
        $plans = TransportPlan::active()
            ->orderBy('plan_type')
            ->orderBy('sort_order')
            ->orderBy('allowed_days_per_week')
            ->get();

        return ApiResponse::success([
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name_ar' => $plan->name_ar,
                    'name_en' => $plan->name_en,
                    'plan_type' => $plan->plan_type,
                    'allowed_days_per_week' => $plan->allowed_days_per_week,
                    'sort_order' => $plan->sort_order,
                ];
            }),
        ]);
    }

    /**
     * Get a specific plan.
     */
    public function show($id): JsonResponse
    {
        $plan = TransportPlan::find($id);

        if (!$plan) {
            return ApiResponse::error('Plan not found', null, 404);
        }

        if (!$plan->active) {
            return ApiResponse::error('Plan is not active', null, 404);
        }

        return ApiResponse::success([
            'plan' => [
                'id' => $plan->id,
                'name_ar' => $plan->name_ar,
                'name_en' => $plan->name_en,
                'plan_type' => $plan->plan_type,
                'allowed_days_per_week' => $plan->allowed_days_per_week,
                'sort_order' => $plan->sort_order,
            ],
        ]);
    }
}
