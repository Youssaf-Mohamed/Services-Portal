<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusScheduleSlot;
use App\Models\TransportSubscription;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManifestController extends Controller
{
    /**
     * Get manifest data for a specific route, day, and time.
     */
    public function index(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:bus_routes,id',
            'day_of_week' => 'required|integer|between:0,6',
            'time' => 'required|date_format:H:i',
        ]);

        $slot = BusScheduleSlot::with('route')
            ->where('route_id', $request->route_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('time', $request->time)
            ->first();

        if (!$slot) {
            return ApiResponse::error('Slot not found', null, 404);
        }

        // Get active subscriptions with seat reservations (not waitlisted)
        $subscriptions = TransportSubscription::with(['user', 'route'])
            ->where('slot_id', $slot->id)
            ->where('status', 'active')
            ->whereHas('reservation', function ($query) {
                $query->whereNull('released_at');
            })
            ->get();

        $manifest = $subscriptions->map(function ($sub) {
            return [
                'subscription_id' => $sub->id,
                'student_name' => $sub->user->name,
                'student_email' => $sub->user->email,
                'student_id' => $sub->user->student_id ?? null,
                'plan_type' => $sub->plan_type,
                'start_date' => $sub->start_date->format('Y-m-d'),
                'end_date' => $sub->end_date->format('Y-m-d'),
            ];
        });

        return ApiResponse::success([
            'slot' => [
                'id' => $slot->id,
                'route_name' => $slot->route->name_en,
                'day_of_week' => $slot->day_of_week,
                'time' => $slot->time,
                'direction' => $slot->direction,
                'capacity' => $slot->capacity,
            ],
            'total_passengers' => $manifest->count(),
            'manifest' => $manifest,
        ]);
    }

    /**
     * Export manifest as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'route_id' => 'required|exists:bus_routes,id',
            'day_of_week' => 'required|integer|between:0,6',
            'time' => 'required|date_format:H:i',
        ]);

        $slot = BusScheduleSlot::with('route')
            ->where('route_id', $request->route_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('time', $request->time)
            ->first();

        if (!$slot) {
            abort(404, 'Slot not found');
        }

        // Get active subscriptions with seat reservations
        $subscriptions = TransportSubscription::with(['user', 'route'])
            ->where('slot_id', $slot->id)
            ->where('status', 'active')
            ->whereHas('reservation', function ($query) {
                $query->whereNull('released_at');
            })
            ->get();

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayName = $dayNames[$slot->day_of_week] ?? 'Unknown';
        $fileName = sprintf(
            'manifest_%s_%s_%s.csv',
            str_replace(' ', '_', $slot->route->name_en),
            $dayName,
            str_replace(':', '', $slot->time)
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->stream(function () use ($subscriptions, $slot, $dayName) {
            $output = fopen('php://output', 'w');

            // Header row
            fputcsv($output, [
                'Route: ' . $slot->route->name_en,
                'Day: ' . $dayName,
                'Time: ' . $slot->time,
                'Total: ' . $subscriptions->count(),
            ]);
            fputcsv($output, []); // Empty row

            // Column headers
            fputcsv($output, [
                'Student Name',
                'Email',
                'Student ID',
                'Plan Type',
                'Start Date',
                'End Date',
            ]);

            // Data rows
            foreach ($subscriptions as $sub) {
                fputcsv($output, [
                    $sub->user->name,
                    $sub->user->email,
                    $sub->user->student_id ?? 'N/A',
                    ucfirst($sub->plan_type),
                    $sub->start_date->format('Y-m-d'),
                    $sub->end_date->format('Y-m-d'),
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}
