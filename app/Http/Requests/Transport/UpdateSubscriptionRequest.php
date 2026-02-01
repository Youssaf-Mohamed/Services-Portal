<?php

namespace App\Http\Requests\Transport;

use App\Enums\Transport\PlanType;
use App\Models\BusRoute;
use App\Models\BusScheduleSlot;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $proofMaxSize = config('transport.proof_max_size', 2048);
        
        return [
            // Route/Slot info usually shouldn't change for a simple correction, 
            // but if they want to change slot, let them.
            'route_id' => ['required', 'integer', 'exists:bus_routes,id'],
            'day_of_week' => ['required_without:plan_id', 'integer', 'min:0', 'max:6'],
            'time' => ['required_without:plan_id', 'date_format:H:i'],
            'plan_type' => ['required', Rule::enum(PlanType::class)],
            'plan_id' => ['nullable', 'integer', 'exists:transport_plans,id'],
            'selected_days' => ['required_with:plan_id', 'array'],
            'selected_days.*' => ['string'],
            
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'paid_from_number' => ['required', 'string', 'min:8', 'max:30'],
            'paid_at' => ['required', 'date'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            
            // Proof is optional on update
            'proof' => ['nullable', 'file', 'mimes:jpeg,png,webp', "max:{$proofMaxSize}"],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            
            // Validate route is active
            $route = BusRoute::find($this->route_id);
            if (!$route || !$route->active) {
                $validator->errors()->add('route_id', 'The selected route is not active.');
                return;
            }
            
            // Validate payment method is active
            $paymentMethod = PaymentMethod::find($this->payment_method_id);
            if (!$paymentMethod || !$paymentMethod->active) {
                $validator->errors()->add('payment_method_id', 'The selected payment method is not active.');
                return;
            }
            
            // Legacy Slot Validation (Only if no plan_id)
            if (!$this->plan_id) {
                // Validate the slot exists for route + day_of_week + time
                $slot = BusScheduleSlot::where('route_id', $this->route_id)
                    ->where('day_of_week', $this->day_of_week)
                    ->where('time', $this->time . ':00') 
                    ->where('direction', 'round_trip')
                    ->where('active', true)
                    ->first();
                
                if (!$slot) {
                    $slot = BusScheduleSlot::where('route_id', $this->route_id)
                        ->where('day_of_week', $this->day_of_week)
                        ->where('time', $this->time)
                        ->where('direction', 'round_trip')
                        ->where('active', true)
                        ->first();
                }
                
                if (!$slot) {
                    $validator->errors()->add('time', 'No active slot found for the selected route, day, and time.');
                    return;
                }
                
                $this->merge(['slot_id' => $slot->id]);
            } else {
                $this->merge(['slot_id' => null]);
            }
        });
    }

    public function attributes(): array
    {
        return [
            'route_id' => 'route',
            'day_of_week' => 'day',
            'time' => 'departure time',
            'plan_type' => 'subscription plan',
            'payment_method_id' => 'payment method',
            'paid_from_number' => 'payment phone number',
            'paid_at' => 'payment date',
            'amount_paid' => 'amount paid',
            'proof' => 'payment proof',
        ];
    }

    public function messages(): array
    {
        return [
            'proof.max' => 'The payment proof must not be larger than ' . (config('transport.proof_max_size', 2048) / 1024) . 'MB.',
        ];
    }
}
