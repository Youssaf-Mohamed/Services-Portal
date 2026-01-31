<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRouteRequest;
use App\Http\Requests\Admin\UpdateRouteRequest;
use App\Http\Resources\Admin\RouteResource;
use App\Models\BusRoute;
use App\Models\BusStop;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    /**
     * List all routes with stops count and slots count.
     */
    public function index(Request $request)
    {
        $query = BusRoute::withCount(['stops', 'slots']);

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $routes = $query->orderBy('name_en')->get();

        return ApiResponse::success([
            'routes' => RouteResource::collection($routes),
        ]);
    }

    /**
     * Create a new route.
     */
    public function store(StoreRouteRequest $request)
    {
        $route = BusRoute::create([
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'price_one_way' => $request->price_one_way,
            'monthly_discount_percent' => $request->monthly_discount_percent ?? 0,
            'term_discount_percent' => $request->term_discount_percent ?? 0,
            'active' => $request->boolean('active', true),
        ]);

        $route->loadCount(['stops', 'slots']);

        return ApiResponse::success(new RouteResource($route), 'Route created successfully', 201);
    }

    /**
     * Get a single route with stops and slots count.
     */
    public function show($id)
    {
        $route = BusRoute::with(['stops' => function ($query) {
            $query->orderBy('bus_route_stops.sort_order');
        }])->withCount('slots')->find($id);

        if (!$route) {
            return ApiResponse::error('Route not found', null, 404);
        }

        return ApiResponse::success(new RouteResource($route));
    }

    /**
     * Update a route.
     */
    public function update(UpdateRouteRequest $request, $id)
    {
        $route = BusRoute::find($id);

        if (!$route) {
            return ApiResponse::error('Route not found', null, 404);
        }

        $route->update([
            'name_ar' => $request->name_ar ?? $route->name_ar,
            'name_en' => $request->name_en ?? $route->name_en,
            'price_one_way' => $request->price_one_way ?? $route->price_one_way,
            'monthly_discount_percent' => $request->monthly_discount_percent ?? $route->monthly_discount_percent,
            'term_discount_percent' => $request->term_discount_percent ?? $route->term_discount_percent,
            'active' => $request->has('active') ? $request->boolean('active') : $route->active,
        ]);

        $route->loadCount(['stops', 'slots']);
        $route->load(['stops' => function ($query) {
            $query->orderBy('bus_route_stops.sort_order');
        }]);

        return ApiResponse::success(new RouteResource($route), 'Route updated successfully');
    }

    /**
     * Update route stops (attach/detach/reorder).
     */
    public function updateStops(Request $request, $id)
    {
        $route = BusRoute::find($id);

        if (!$route) {
            return ApiResponse::error('Route not found', null, 404);
        }

        $request->validate([
            'stops' => 'required|array',
            'stops.*.id' => 'required|exists:bus_stops,id',
            'stops.*.sort_order' => 'required|integer|min:1',
        ]);

        // Check for duplicate sort_order values
        $sortOrders = collect($request->stops)->pluck('sort_order');
        if ($sortOrders->count() !== $sortOrders->unique()->count()) {
            return ApiResponse::error('Duplicate sort_order values are not allowed', null, 422);
        }

        // Check for duplicate stop IDs
        $stopIds = collect($request->stops)->pluck('id');
        if ($stopIds->count() !== $stopIds->unique()->count()) {
            return ApiResponse::error('Duplicate stop IDs are not allowed', null, 422);
        }

        DB::transaction(function () use ($route, $request) {
            // Detach all existing stops
            $route->stops()->detach();

            // Attach new stops with sort_order
            foreach ($request->stops as $stopData) {
                $route->stops()->attach($stopData['id'], [
                    'sort_order' => $stopData['sort_order'],
                ]);
            }
        });

        $route->load(['stops' => function ($query) {
            $query->orderBy('bus_route_stops.sort_order');
        }]);
        $route->loadCount(['stops', 'slots']);

        return ApiResponse::success(new RouteResource($route), 'Route stops updated successfully');
    }
}
