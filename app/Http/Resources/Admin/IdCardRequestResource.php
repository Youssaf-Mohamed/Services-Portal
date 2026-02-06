<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdCardRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array (list view).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'type' => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name_ar' => $this->type->name_ar,
                'name_en' => $this->type->name_en,
            ],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'amount' => (float) $this->amount_snapshot,
            'current_type_fee' => (float) $this->type->fee,
            'transaction_number' => $this->transaction_number,
            'transfer_time' => $this->transfer_time?->toIso8601String(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'payment_status_color' => $this->payment_status->color(),
            'created_at' => $this->created_at->toIso8601String(),
            'approval_info' => $this->status->value === 'approved' ? [
                'by' => $this->reviewer?->name ?? 'System',
                'at' => $this->reviewed_at?->toIso8601String(),
            ] : null,
            'rejection_info' => $this->status->value === 'rejected' ? [
                'by' => $this->reviewer?->name ?? 'System',
                'at' => $this->reviewed_at?->toIso8601String(),
                'reason' => $this->rejection_reason,
            ] : null,
        ];
    }
}
