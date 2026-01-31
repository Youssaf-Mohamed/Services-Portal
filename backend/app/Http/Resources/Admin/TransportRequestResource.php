<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pricingSummary = null;
        if ($this->pricing_snapshot) {
            $pricingSummary = [
                'final_amount' => $this->pricing_snapshot['final_amount'] ?? $this->amount_expected,
                'discount_percent' => $this->pricing_snapshot['discount_percent'] ?? 0,
            ];
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'plan_type' => $this->plan_type,
            'direction' => $this->direction,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'route' => $this->route ? [
                'id' => $this->route->id,
                'name_ar' => $this->route->name_ar,
                'name_en' => $this->route->name_en,
            ] : null,
            'slot' => $this->slot ? [
                'id' => $this->slot->id,
                'day_of_week' => $this->slot->day_of_week,
                'time' => $this->slot->time,
                'direction' => $this->slot->direction,
                'capacity' => $this->slot->capacity,
            ] : null,
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
            ] : null,
            'pricing_summary' => $pricingSummary,
            'amount_expected' => (float) $this->amount_expected,
            'has_proof' => !empty($this->proof_path),
        ];
    }
}
