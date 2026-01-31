<?php

namespace App\Http\Resources\Transport;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentRouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'active' => $this->active,
            'pricing' => [
                'price_one_way' => (float) $this->price_one_way,
                'monthly_discount_percent' => $this->monthly_discount_percent,
                'term_discount_percent' => $this->term_discount_percent,
            ],
            'stops' => $this->formatStops(),
            'slots' => $this->formatSlots(),
        ];
    }

    /**
     * Format stops with sort_order from pivot.
     */
    protected function formatStops(): array
    {
        return $this->stops->map(function ($stop) {
            return [
                'id' => $stop->id,
                'name_ar' => $stop->name_ar,
                'name_en' => $stop->name_en,
                'lat' => $stop->lat,
                'lng' => $stop->lng,
                'sort_order' => $stop->pivot->sort_order,
            ];
        })->toArray();
    }

    /**
     * Format slots grouped by day_of_week.
     * Returns: { "1": ["07:30", "14:30"], "2": [...], ... }
     */
    protected function formatSlots(): array
    {
        $grouped = [];
        
        foreach ($this->slots as $slot) {
            if (!$slot->active) continue;
            
            $day = (string) $slot->day_of_week;
            $time = substr($slot->time, 0, 5); // HH:MM only
            
            if (!isset($grouped[$day])) {
                $grouped[$day] = [];
            }
            
            // Check if time already added (unlikely with unique constraints but good safety)
            $existing = collect($grouped[$day])->firstWhere('time', $time);
            if (!$existing) {
                $grouped[$day][] = [
                    'id' => $slot->id,
                    'time' => $time,
                    'capacity' => $slot->capacity,
                    'reserved' => $slot->reservations_count ?? 0,
                    'seats_available' => max(0, $slot->capacity - ($slot->reservations_count ?? 0)),
                ];
            }
        }
        
        // Sort times within each day

        
        return $grouped;
    }
}
