<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentIdCardRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => [
                'code' => $this->type->code,
                'name_ar' => $this->type->name_ar,
                'name_en' => $this->type->name_en,
            ],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'amount' => (float) $this->amount_snapshot,
            'transaction_number' => $this->transaction_number,
            'transfer_time' => $this->transfer_time?->toIso8601String(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'has_new_photo' => $this->hasNewPhoto(),
            'issue_description' => $this->when(
                $this->type->requires_description,
                $this->issue_description
            ),
            'rejection_reason' => $this->when(
                $this->status->value === 'rejected',
                $this->rejection_reason
            ),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
