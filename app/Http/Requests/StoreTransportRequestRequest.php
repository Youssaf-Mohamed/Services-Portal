<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransportRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'plan_id' => ['required', 'integer', 'exists:transport_plans,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'pickup_stop_id' => ['required', 'integer', 'exists:bus_stops,id'],
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after:from_date'],
            'slot_id' => ['nullable', 'integer', 'exists:transport_slots,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'route_id.required' => 'Please select a route',
            'route_id.exists' => 'Selected route is invalid',
            'plan_id.required' => 'Please select a plan',
            'payment_method_id.required' => 'Please select a payment method',
            'from_date.after_or_equal' => 'Start date cannot be in the past',
            'to_date.after' => 'End date must be after start date',
        ];
    }
}
