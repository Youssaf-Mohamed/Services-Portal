<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportSubscription;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    /**
     * Export active subscriptions to CSV.
     */
    public function exportActiveSubscriptions()
    {
        return $this->exportCsv(
            'active_subscriptions_' . now()->format('Y-m-d') . '.csv',
            function () {
                $query = TransportSubscription::with(['user', 'route', 'slot', 'plan'])
                    ->activeOn(now())
                    ->orderBy('route_id')
                    ->orderBy('slot_id');

                $this->writeHeader([
                    'Student Name', 'Student ID', 'Email', 'Route', 'Time', 'Day', 'Plan Type', 'Start Date', 'End Date'
                ]);

                $query->chunk(100, function ($subscriptions) {
                    foreach ($subscriptions as $sub) {
                        $this->writeRow([
                            $sub->user->name,
                            $sub->user->student_id ?? 'N/A',
                            $sub->user->email,
                            $sub->route->name_en,
                            $sub->slot->time,
                            $sub->slot->day_of_week,
                            $sub->plan_type,
                            $sub->starts_at?->format('Y-m-d'),
                            $sub->ends_at?->format('Y-m-d'),
                        ]);
                    }
                });
            }
        );
    }

    /**
     * Export waitlisted students to CSV.
     */
    public function exportWaitlist()
    {
        return $this->exportCsv(
            'waitlist_' . now()->format('Y-m-d') . '.csv',
            function () {
                $query = TransportSubscription::with(['user', 'route', 'slot'])
                    ->where('status', 'waitlisted')
                    ->orderBy('created_at');

                $this->writeHeader([
                    'Student Name', 'Student ID', 'Email', 'Route', 'Time', 'Day', 'Request Date'
                ]);

                $query->chunk(100, function ($subscriptions) {
                    foreach ($subscriptions as $sub) {
                        $this->writeRow([
                            $sub->user->name,
                            $sub->user->student_id ?? 'N/A',
                            $sub->user->email,
                            $sub->route->name_en,
                            $sub->slot->time,
                            $sub->slot->day_of_week,
                            $sub->created_at->format('Y-m-d H:i'),
                        ]);
                    }
                });
            }
        );
    }

    /**
     * Export manifest based on filters.
     */
    public function exportManifest(Request $request)
    {
        return $this->exportCsv(
            'manifest_' . now()->format('Y-m-d') . '.csv',
            function () use ($request) {
                $query = TransportSubscription::with(['user', 'route', 'slot', 'plan']);

                // Apply filters (same as dashboard)
                if ($request->filled('route_id')) {
                    $query->where('route_id', $request->route_id);
                }
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('selected_day')) {
                    $query->withDay($request->selected_day);
                }
                if (filter_var($request->active_only, FILTER_VALIDATE_BOOLEAN)) {
                    $query->activeOn(now());
                }

                $this->writeHeader([
                    'ID', 'Student', 'Student ID', 'Route', 'Slot', 'Status', 'Plan', 'Days', 'Start', 'End', 'Payment'
                ]);

                $query->chunk(100, function ($subscriptions) {
                    foreach ($subscriptions as $sub) {
                        $this->writeRow([
                            $sub->id,
                            $sub->user->name,
                            $sub->user->student_id ?? '',
                            $sub->route->name_en,
                            $sub->slot->time . ' (' . $sub->slot->day_of_week . ')',
                            $sub->status,
                            $sub->plan_type,
                            is_array($sub->selected_days) ? implode(', ', $sub->selected_days) : $sub->selected_days,
                            $sub->starts_at?->format('Y-m-d'),
                            $sub->ends_at?->format('Y-m-d'),
                            $sub->amount_paid_expected,
                        ]);
                    }
                });
            }
        );
    }

    /**
     * Helper to generate streamed CSV response.
     */
    private function exportCsv(string $filename, callable $callback)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($callback) {
            $this->output = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility with UTF-8
            if ($this->output) {
                fputs($this->output, "\xEF\xBB\xBF");
                $callback();
                fclose($this->output);
            }
        }, 200, $headers);
    }

    private $output;

    private function writeHeader(array $headers)
    {
        if ($this->output) {
            fputcsv($this->output, $headers);
        }
    }

    private function writeRow(array $row)
    {
        if ($this->output) {
            fputcsv($this->output, $row);
        }
    }
}
