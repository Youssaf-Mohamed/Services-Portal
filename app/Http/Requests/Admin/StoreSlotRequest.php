<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => 'required|exists:bus_routes,id',
            'day_of_week' => 'required|integer|between:0,6',
            'direction' => 'required|in:one_way,round_trip',
            'time' => 'required|date_format:H:i',
            'capacity' => 'required|integer|min:1',
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
