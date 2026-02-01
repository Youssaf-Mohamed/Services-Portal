<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class StudentSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'status' => $this->status,
            'start_date' => $this->starts_at?->toDateString(),
            'end_date' => $this->ends_at?->toDateString(),
            'days_remaining' => $this->computeDaysRemaining(),
            'can_renew' => $this->computeDaysRemaining() <= 7 && $this->status === 'active',
            'route' => [
                'id' => $this->route->id,
                'name_ar' => $this->route->name_ar,
                'name_en' => $this->route->name_en,
            ],
            'slot' => $this->slot ? [
                'day_of_week' => $this->slot->day_of_week,
                'time' => substr($this->slot->time, 0, 5),
                'direction' => $this->slot->direction ?? 'round_trip',
            ] : null,
            'plan' => $this->plan ? [
                'id' => $this->plan->id,
                'name_ar' => $this->plan->name_ar,
                'name_en' => $this->plan->name_en,
            ] : null,
            'selected_days' => $this->selected_days,
            'pricing' => $this->when($this->request?->pricing_snapshot, function () {
                $snapshot = $this->request->pricing_snapshot;
                return [
                    'final_amount' => $snapshot['pricing']['final_amount'] ?? $this->amount_paid_expected,
                    'plan_type' => $snapshot['plan_type'] ?? $this->plan_type,
                ];
            }),
            'amount_paid_expected' => (float) $this->amount_paid_expected,
            'plan_type' => $this->plan_type,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Compute days remaining until subscription ends.
     */
    protected function computeDaysRemaining(): int
    {
        if (!$this->ends_at) {
            return 0;
        }
        
        $endDate = Carbon::parse($this->ends_at)->endOfDay();
        $today = Carbon::now()->startOfDay();
        
        $diff = $today->diffInDays($endDate, false);
        return max(0, (int) $diff);
    }
}
