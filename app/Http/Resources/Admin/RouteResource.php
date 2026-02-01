<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stops = [];
        if ($this->relationLoaded('stops')) {
            $stops = $this->stops->map(fn($stop) => [
                'id' => $stop->id,
                'name_ar' => $stop->name_ar,
                'name_en' => $stop->name_en,
                'sort_order' => $stop->pivot->sort_order ?? 0,
            ])->sortBy('sort_order')->values()->toArray();
        }

        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'active' => $this->active,
            'pricing' => [
                'price_one_way' => (float) $this->price_one_way,
                'monthly_discount_percent' => (float) ($this->monthly_discount_percent ?? 0),
                'term_discount_percent' => (float) ($this->term_discount_percent ?? 0),
            ],
            'stops' => $stops,
            'stops_count' => $this->stops_count ?? count($stops),
            'slots_count' => $this->slots_count ?? 0,
        ];
    }
}
