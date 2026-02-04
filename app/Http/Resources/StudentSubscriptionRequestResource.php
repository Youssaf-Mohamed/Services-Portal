<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentSubscriptionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'plan_type' => $this->plan_type,
            'route' => [
                'id' => $this->route->id,
                'name_ar' => $this->route->name_ar,
                'name_en' => $this->route->name_en,
            ],
            'slot' => $this->slot ? [
                'day_of_week' => $this->slot->day_of_week,
                'time' => substr($this->slot->time, 0, 5),
                'direction' => $this->slot->direction ?? $this->direction,
            ] : null,
            'plan' => $this->plan ? [
                'id' => $this->plan->id,
                'name_ar' => $this->plan->name_ar,
                'name_en' => $this->plan->name_en,
            ] : null,
            'selected_days' => $this->selected_days,
            'amount_paid' => (float) $this->amount_expected,
            'paid_at' => $this->paid_at?->toDateString(),
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
            ] : null,
            'pricing' => $this->when($this->pricing_snapshot, [
                'final_amount' => $this->pricing_snapshot['pricing']['final_amount'] ?? null,
                'discount_percent' => $this->pricing_snapshot['pricing']['discount_percent'] ?? null,
            ]),
            'payment_status' => $this->payment_status,
            'payment_flag_reason' => $this->payment_flag_reason,
            'proof_url' => $this->proof_path ? url("api/admin/transport/requests/{$this->id}/proof") : null,
            'plan_id' => $this->plan_id,
            'payment_method_id' => $this->payment_method_id,
            'paid_from_number' => $this->paid_from_number,
            'rejection_reason' => $this->rejection_reason,
            'subscription_id' => $this->subscription?->id,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
