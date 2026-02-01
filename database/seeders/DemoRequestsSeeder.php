<?php

namespace Database\Seeders;

use App\Models\BusRoute;
use App\Models\BusScheduleSlot;
use App\Models\PaymentMethod;
use App\Models\TransportSubscriptionRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoRequestsSeeder extends Seeder
{
    /**
     * Create demo transport subscription requests for testing admin approval flow.
     */
    public function run(): void
    {
        // Get the student user
        $student = User::where('email', 'student@test.com')->first();
        
        if (!$student) {
            $this->command->error('Student user not found! Run TestUserSeeder first.');
            return;
        }

        // Get first route and slot
        $route = BusRoute::first();
        $slot = BusScheduleSlot::where('route_id', $route?->id)->first();
        $paymentMethod = PaymentMethod::first();

        if (!$route || !$slot || !$paymentMethod) {
            $this->command->error('Routes, slots, or payment methods not found! Run other seeders first.');
            return;
        }

        // Check if student already has a pending request
        $existingRequest = TransportSubscriptionRequest::where('user_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            $this->command->info('Student already has a pending request. Skipping.');
            return;
        }

        // Create a pending subscription request
        $request = TransportSubscriptionRequest::create([
            'user_id' => $student->id,
            'route_id' => $route->id,
            'slot_id' => $slot->id,
            'plan_type' => 'monthly',
            'direction' => 'round_trip',
            'status' => 'pending',
            'payment_method_id' => $paymentMethod->id,
            'paid_from_number' => '01234567890',
            'paid_at' => now(),
            'proof_path' => null, // No proof file for demo
            'amount_expected' => 500.00,
            'pricing_snapshot' => [
                'route_id' => $route->id,
                'slot_id' => $slot->id,
                'plan_type' => 'monthly',
                'pricing' => [
                    'base_price' => 600.00,
                    'discount_percent' => 10,
                    'discount_amount' => 60.00,
                    'final_amount' => 540.00,
                ],
                'computed_at' => now()->toIso8601String(),
            ],
        ]);

        $this->command->info("Demo subscription request created: ID {$request->id}");
        $this->command->info("Student: {$student->name} ({$student->email})");
        $this->command->info("Route: {$route->name_en}");
        $this->command->info("Status: pending");
    }
}
