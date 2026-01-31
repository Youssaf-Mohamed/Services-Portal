<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdCardRequestDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array (detail view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // User info
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            
            // Type info
            'type' => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name_ar' => $this->type->name_ar,
                'name_en' => $this->type->name_en,
                'requires_photo' => $this->type->requires_photo,
                'requires_description' => $this->type->requires_description,
            ],
            
            // Status info
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            
            // Pricing
            'amount_snapshot' => (float) $this->amount_snapshot,
            'current_type_fee' => (float) $this->type->fee,
            'fee_changed' => abs($this->amount_snapshot - $this->type->fee) > 0.01,
            
            // Payment info
            'payment' => [
                'transaction_number' => $this->transaction_number,
                'transfer_time' => $this->transfer_time?->toIso8601String(),
                'status' => $this->payment_status->value,
                'status_label' => $this->payment_status->label(),
                'status_color' => $this->payment_status->color(),
                'flag_reason' => $this->payment_flag_reason,
                'verified_at' => $this->payment_verified_at?->toIso8601String(),
                'verified_by' => $this->paymentVerifier ? [
                    'id' => $this->paymentVerifier->id,
                    'name' => $this->paymentVerifier->name,
                ] : null,
            ],
            
            // Service-specific fields
            'has_screenshot' => !empty($this->transfer_screenshot_path),
            'has_new_photo' => !empty($this->new_photo_path),
            'issue_description' => $this->issue_description,
            
            // Rejection
            'rejection_reason' => $this->rejection_reason,
            
            // Workflow actors
            'reviewer' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'deliverer' => $this->deliverer ? [
                'id' => $this->deliverer->id,
                'name' => $this->deliverer->name,
            ] : null,
            
            // Timestamps
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Allowed actions (for frontend button states)
            'can' => [
                'verify_payment' => $this->canVerifyPayment(),
                'flag_payment' => $this->canFlagPayment(),
                'approve' => $this->canBeApproved(),
                'reject' => $this->canBeRejected(),
                'ready' => $this->canBeReadied(),
                'deliver' => $this->canBeDelivered(),
            ],
        ];
    }
}
