<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportSubscriptionRequest;
use App\Models\TransportSubscription;
use App\Models\BusRoute;
use App\Models\BusScheduleSlot;
use App\Support\ApiResponse;

class TransportDashboardController extends Controller
{
    /**
     * Get transport management dashboard KPIs.
     */
    public function dashboard()
    {
        $pendingRequests = TransportSubscriptionRequest::where('status', 'pending')->count();
        $activeSubscriptions = TransportSubscription::where('status', 'active')->count();
        $waitlistedSubscriptions = TransportSubscription::where('status', 'waitlisted')->count();
        $activeRoutes = BusRoute::where('active', true)->count();
        $activeRoutes = BusRoute::where('active', true)->count();
        $activeSlots = BusScheduleSlot::where('active', true)->count();

        // Financials
        $totalRevenue = TransportSubscription::where('status', 'active')->sum('amount_paid_expected');
        $potentialRevenue = TransportSubscriptionRequest::where('status', 'pending')->sum('amount_expected');

        return ApiResponse::success([
            'pending_requests' => $pendingRequests,
            'active_subscriptions' => $activeSubscriptions,
            'waitlisted_subscriptions' => $waitlistedSubscriptions,
            'active_routes' => $activeRoutes,
            'active_slots' => $activeSlots,
            'financials' => [
                'total_revenue' => (float) $totalRevenue,
                'potential_revenue' => (float) $potentialRevenue,
            ],
        ]);
    }

    /**
     * Get manifest of all subscriptions with filters.
     */
    public function manifest(\Illuminate\Http\Request $request)
    {
        $query = TransportSubscription::with([
            'user',
            'route',
            'slot',
            'plan',
            'request.paymentMethod'
        ]);

        // Filter by route
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by selected day
        if ($request->filled('selected_day')) {
            $query->withDay($request->selected_day);
        }

        // Filter active only (ends_at >= today)
        if ($request->filled('active_only')) {
            $activeOnly = filter_var($request->active_only, FILTER_VALIDATE_BOOLEAN);
            if ($activeOnly) {
                $query->activeOn(now());
            }
        }

        // Order by most recent
        $query->orderBy('created_at', 'desc');

        $subscriptions = $query->paginate($request->get('per_page', 15));

        return ApiResponse::success([
            'subscriptions' => $subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'student' => [
                        'id' => $sub->user->id,
                        'name' => $sub->user->name,
                        'email' => $sub->user->email,
                    ],
                    'route' => [
                        'id' => $sub->route->id,
                        'name_ar' => $sub->route->name_ar,
                        'name_en' => $sub->route->name_en,
                    ],
                    'slot' => $sub->slot ? [
                        'id' => $sub->slot->id,
                        'time' => $sub->slot->time,
                        'day_of_week' => $sub->slot->day_of_week,
                    ] : null,
                    'plan' => $sub->plan ? [
                        'id' => $sub->plan->id,
                        'name_ar' => $sub->plan->name_ar,
                        'name_en' => $sub->plan->name_en,
                        'plan_type' => $sub->plan->plan_type,
                        'allowed_days_per_week' => $sub->plan->allowed_days_per_week,
                    ] : null,
                    'plan_type' => $sub->plan_type,
                    'selected_days' => $sub->selected_days,
                    'status' => $sub->status,
                    'starts_at' => $sub->starts_at?->toIso8601String(),
                    'ends_at' => $sub->ends_at?->toIso8601String(),
                    'amount_paid_expected' => (float) $sub->amount_paid_expected,
                    'payment_method' => $sub->request?->paymentMethod ? [
                        'name' => $sub->request->paymentMethod->name,
                    ] : null,
                    'created_at' => $sub->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }
}
