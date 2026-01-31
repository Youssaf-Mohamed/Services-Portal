<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route' => $this->route ? [
                'id' => $this->route->id,
                'name_en' => $this->route->name_en,
            ] : null,
            'day_of_week' => $this->day_of_week,
            'time' => $this->time,
            'direction' => $this->direction,
            'capacity' => $this->capacity,
            'active' => $this->active,
            'active_reservations_count' => $this->active_reservations_count ?? 0,
            'capacity_remaining' => $this->capacity_remaining ?? $this->capacity,
        ];
    }
}
