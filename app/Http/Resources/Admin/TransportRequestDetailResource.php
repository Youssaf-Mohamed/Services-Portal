<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TransportRequestDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = null;
        if ($this->subscription) {
            $daysRemaining = null;
            if ($this->subscription->end_date && $this->subscription->status === 'active') {
                $daysRemaining = max(0, now()->diffInDays($this->subscription->end_date, false));
            }

            $subscription = [
                'id' => $this->subscription->id,
                'status' => $this->subscription->status,
                'start_date' => $this->subscription->start_date?->format('Y-m-d'),
                'end_date' => $this->subscription->end_date?->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
            ];
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'plan_type' => $this->plan_type,
            'direction' => $this->direction,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // User info
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'student_id' => $this->user->student_id ?? null,
            ],
            
            // Route info
            'route' => $this->route ? [
                'id' => $this->route->id,
                'name_ar' => $this->route->name_ar,
                'name_en' => $this->route->name_en,
                'stops' => $this->route->stops->map(fn($stop) => [
                    'id' => $stop->id,
                    'name_ar' => $stop->name_ar,
                    'name_en' => $stop->name_en,
                ])->toArray(),
            ] : null,
            
            // Slot info
            'slot' => $this->slot ? [
                'id' => $this->slot->id,
                'day_of_week' => $this->slot->day_of_week,
                'time' => $this->slot->time,
                'direction' => $this->slot->direction,
                'capacity' => $this->slot->capacity,
            ] : null,
            
            // Payment info
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
            ] : null,
            'paid_from_number' => $this->paid_from_number,
            'paid_at' => $this->paid_at?->toIso8601String(),
            
            // Pricing
            'pricing_snapshot' => $this->pricing_snapshot,
            'amount_expected' => (float) $this->amount_expected,
            
            // Proof
            'proof_exists' => !empty($this->proof_path) && Storage::disk('proofs')->exists($this->proof_path),
            
            // Payment Verification
            'payment_status' => $this->payment_status ?? 'pending_verification',
            'payment_flag_reason' => $this->payment_flag_reason,
            'payment_verified_at' => $this->payment_verified_at?->toIso8601String(),
            
            // Rejection
            'rejection_reason' => $this->rejection_reason,
            
            // Approver
            'approver' => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
                'email' => $this->approver->email,
            ] : null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            
            // Subscription
            'subscription' => $subscription,
        ];
    }
}
