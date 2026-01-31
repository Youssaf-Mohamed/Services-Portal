<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => 'sometimes|exists:bus_routes,id',
            'day_of_week' => 'sometimes|integer|between:0,6',
            'direction' => 'sometimes|in:one_way,round_trip',
            'time' => 'sometimes|date_format:H:i',
            'capacity' => 'sometimes|integer|min:1',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'direction.in' => 'Direction must be one_way or round_trip.',
            'day_of_week.between' => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
        ];
    }
}
