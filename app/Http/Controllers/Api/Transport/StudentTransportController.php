<?php

namespace App\Http\Controllers\Api\Transport;

use App\Http\Controllers\Controller;
use App\Http\Resources\Transport\StudentRouteResource;
use App\Models\BusRoute;
use App\Models\PaymentMethod;
use App\Models\TransportSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StudentTransportController extends Controller
{
    /**
     * Get all active bus routes with stops and slots.
     * 
     * @return JsonResponse
     */
    public function routes(): JsonResponse
    {
        $routes = BusRoute::active()
            ->with([
                'stops' => function ($query) {
                    $query->orderBy('bus_route_stops.sort_order');
                },
                'slots' => function ($query) {
                    $query->where('active', true)
                          ->withCount(['activeReservations as reservations_count'])
                          ->orderBy('day_of_week')
                          ->orderBy('time');
                }
            ])
            ->get();

        return ApiResponse::success(
            StudentRouteResource::collection($routes)
        );
    }

    /**
     * Get all active payment methods.
     * 
     * @return JsonResponse
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = PaymentMethod::active()
            ->select(['id', 'name', 'account_number', 'instructions'])
            ->get();

        return ApiResponse::success($methods);
    }

    /**
     * Get transport settings.
     * Returns the first (and only) settings row.
     * 
     * @return JsonResponse
     */
    public function settings(): JsonResponse
    {
        $settings = TransportSetting::select([
            'id',
            'days_per_week',
            'weeks_in_month',
            'weeks_in_term',
            'show_capacity',
        ])->first();

        if (!$settings) {
            return ApiResponse::error('Transport settings not configured', null, 404);
        }

        return ApiResponse::success($settings);
    }
}
