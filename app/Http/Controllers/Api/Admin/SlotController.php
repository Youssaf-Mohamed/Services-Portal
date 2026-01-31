<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSlotRequest;
use App\Http\Requests\Admin\UpdateSlotRequest;
use App\Http\Resources\Admin\SlotResource;
use App\Models\BusScheduleSlot;
use App\Models\TransportSeatReservation;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    /**
     * List slots with capacity info.
     */
    public function index(Request $request)
    {
        $query = BusScheduleSlot::with('route');

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $slots = $query->orderBy('route_id')
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        // Add active_reservations_count and capacity_remaining
        $slots->each(function ($slot) {
            $slot->active_reservations_count = TransportSeatReservation::where('slot_id', $slot->id)
                ->whereNull('released_at')
                ->count();
            $slot->capacity_remaining = $slot->capacity - $slot->active_reservations_count;
        });

        return ApiResponse::success([
            'slots' => SlotResource::collection($slots),
        ]);
    }

    /**
     * Create a new slot.
     */
    public function store(StoreSlotRequest $request)
    {
        // Check for unique constraint
        $exists = BusScheduleSlot::where('route_id', $request->route_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('direction', $request->direction)
            ->where('time', $request->time)
            ->exists();

        if ($exists) {
            return ApiResponse::error(
                'A slot with the same route, day, direction, and time already exists',
                null,
                409
            );
        }

        $slot = BusScheduleSlot::create([
            'route_id' => $request->route_id,
            'day_of_week' => $request->day_of_week,
            'direction' => $request->direction,
            'time' => $request->time,
            'capacity' => $request->capacity,
            'active' => $request->boolean('active', true),
        ]);

        $slot->load('route');
        $slot->active_reservations_count = 0;
        $slot->capacity_remaining = $slot->capacity;

        return ApiResponse::success(new SlotResource($slot), 'Slot created successfully', 201);
    }

    /**
     * Update a slot.
     */
    public function update(UpdateSlotRequest $request, $id)
    {
        $slot = BusScheduleSlot::find($id);

        if (!$slot) {
            return ApiResponse::error('Slot not found', null, 404);
        }

        // If changing unique fields, check for conflicts
        $routeId = $request->route_id ?? $slot->route_id;
        $dayOfWeek = $request->day_of_week ?? $slot->day_of_week;
        $direction = $request->direction ?? $slot->direction;
        $time = $request->time ?? $slot->time;

        $exists = BusScheduleSlot::where('route_id', $routeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('direction', $direction)
            ->where('time', $time)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return ApiResponse::error(
                'A slot with the same route, day, direction, and time already exists',
                null,
                409
            );
        }

        $slot->update([
            'route_id' => $routeId,
            'day_of_week' => $dayOfWeek,
            'direction' => $direction,
            'time' => $time,
            'capacity' => $request->capacity ?? $slot->capacity,
            'active' => $request->has('active') ? $request->boolean('active') : $slot->active,
        ]);

        $slot->load('route');
        $slot->active_reservations_count = TransportSeatReservation::where('slot_id', $slot->id)
            ->whereNull('released_at')
            ->count();
        $slot->capacity_remaining = $slot->capacity - $slot->active_reservations_count;

        return ApiResponse::success(new SlotResource($slot), 'Slot updated successfully');
    }
}
