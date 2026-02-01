<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdCardSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array (student view).
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_account_number' => $this->payment_account_number,
            'payment_account_name' => $this->payment_account_name,
            'payment_instructions' => $this->payment_instructions,
            'service_enabled' => $this->service_enabled,
        ];
    }
}
