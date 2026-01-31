<?php

namespace App\Http\Controllers\Api;

use App\Enums\Transport\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\StoreSubscriptionRequest;
use App\Http\Resources\StudentSubscriptionRequestResource;
use App\Models\BusRoute;
use App\Models\PaymentMethod;
use App\Models\TransportPlan;
use App\Models\TransportSetting;
use App\Models\TransportSubscription;
use App\Models\TransportSubscriptionRequest;
use App\Services\TransportPricingService;
use App\Support\ApiResponse;
use App\Support\ProofStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StudentTransportRequestController extends Controller
{
    public function __construct(
        protected TransportPricingService $pricingService,
        protected \App\Services\NotificationService $notificationService
    ) {}

    /**
     * Store a newly created subscription request.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // Check for duplicate: block if user has any pending request or active/waitlisted subscription
        $hasPendingRequest = TransportSubscriptionRequest::where('user_id', $user->id)
            ->where('status', RequestStatus::PENDING->value)
            ->exists();
        
        if ($hasPendingRequest) {
            return ApiResponse::error(
                'You already have a pending subscription request. Please wait for it to be reviewed.',
                null,
                409
            );
        }
        
        $activeSubscription = TransportSubscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'waitlisted'])
            ->first();
        
        if ($activeSubscription) {
            // Check if eligible for renewal (ends within 7 days)
            $isRenewal = false;
            
            if ($activeSubscription->status === 'active' && $activeSubscription->ends_at) {
                $daysRemaining = now()->startOfDay()->diffInDays($activeSubscription->ends_at->endOfDay(), false);
                if ($daysRemaining <= 7) {
                    $isRenewal = true;
                }
            }

            if (!$isRenewal) {
                return ApiResponse::error(
                    'You already have an active or waitlisted subscription. Renewal is only available 7 days before expiration.',
                    null,
                    409
                );
            }
        }
        
        // Load dependencies
        $settings = TransportSetting::firstOrFail();
        $route = BusRoute::findOrFail($request->route_id);
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
        
        // Check if this is a plan-based request or legacy
        $isPlanBased = $request->filled('plan_id') && $request->filled('selected_days');
        
        if ($isPlanBased) {
            // NEW PLAN-BASED PRICING
            $plan = TransportPlan::findOrFail($request->plan_id);
            
            // Validate plan is active
            if (!$plan->active) {
                return ApiResponse::error('Selected plan is not active', null, 422);
            }
            
            // Validate selected days
            try {
                $this->pricingService->validateSelectedDays($plan, $request->selected_days);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ApiResponse::error(
                    'Invalid days selection',
                    $e->errors(),
                    422
                );
            }
            
            // Calculate pricing server-side with plan
            $pricingResult = $this->pricingService->calculateWithPlan(
                $route,
                $plan,
                $request->selected_days,
                $settings
            );
            $expectedAmount = $pricingResult['amount'];
            $pricingSnapshot = $pricingResult['breakdown'];
        } else {
            // LEGACY PRICING (backward compatibility)
            $plan = null;
            $pricing = $this->pricingService->calculate(
                $request->plan_type,
                $route,
                $settings
            );
            $expectedAmount = $pricing['final_amount'];
            $pricingSnapshot = $pricing;
        }
        
        // NEW: Check slot capacity BEFORE accepting the request
        if ($request->filled('slot_id')) {
            $slot = \App\Models\TransportSlot::find($request->slot_id);
            
            if (!$slot) {
                return ApiResponse::error('Selected time slot not found', null, 404);
            }
            
            // Count active seat reservations for this slot
            $activeReservations = \App\Models\TransportSeatReservation::where('slot_id', $slot->id)
                ->whereNull('released_at')
                ->count();
            
            $capacityRemaining = $slot->capacity - $activeReservations;
            
            if ($capacityRemaining <= 0) {
                return ApiResponse::error(
                    'This time slot is fully booked. Please select a different time slot.',
                    [
                        'capacity_remaining' => 0,
                        'slot_id' => $slot->id,
                        'slot_time' => $slot->time,
                        'slot_day' => $slot->day_of_week,
                    ],
                    422
                );
            }
            
            Log::channel('daily')->info('Capacity check passed', [
                'slot_id' => $slot->id,
                'capacity_remaining' => $capacityRemaining,
                'requested_by' => $user->id,
            ]);
        }
        
        // Validate amount_paid matches computed final_amount (within 0.01 tolerance)
        $amountPaid = (float) $request->amount_paid;
        
        if (abs($amountPaid - $expectedAmount) > 0.01) {
            return ApiResponse::error(
                'Amount must match the required price of EGP ' . number_format($expectedAmount, 2),
                ['expected_amount' => $expectedAmount, 'provided_amount' => $amountPaid],
                422
            );
        }
        
        // Store proof file privately
        $proofPath = ProofStorage::storeProof($request->file('proof'), $user->id);
        
        // Create the subscription request
        $subscriptionRequest = TransportSubscriptionRequest::create([
            'user_id' => $user->id,
            'route_id' => $route->id,
            'slot_id' => $request->slot_id,
            'plan_id' => $isPlanBased ? $plan->id : null,
            'selected_days' => $isPlanBased ? $request->selected_days : null,
            'plan_type' => $request->plan_type,
            'direction' => 'round_trip',
            'status' => RequestStatus::PENDING->value,
            'payment_method_id' => $paymentMethod->id,
            'paid_from_number' => $request->paid_from_number,
            'paid_at' => $request->paid_at,
            'proof_path' => $proofPath,
            'amount_expected' => $expectedAmount,
            'pricing_snapshot' => [
                'route_id' => $route->id,
                'slot_id' => $request->slot_id,
                'plan_id' => $isPlanBased ? $plan->id : null,
                'plan_type' => $request->plan_type,
                'selected_days' => $isPlanBased ? $request->selected_days : null,
                'pricing' => $pricingSnapshot,
                'computed_at' => now()->toIso8601String(),
            ],
        ]);
        
        // Log the creation event
        Log::channel('daily')->info('Transport request submitted', [
            'user_id' => $user->id,
            'request_id' => $subscriptionRequest->id,
            'route_id' => $route->id,
            'slot_id' => $request->slot_id,
        ]);

        // Send notification
        $this->notificationService->notifyRequestSubmitted($user, $subscriptionRequest->id);
        
        // Load relations for response
        $subscriptionRequest->load(['route', 'slot', 'paymentMethod']);
        
        return ApiResponse::success(
            new StudentSubscriptionRequestResource($subscriptionRequest),
            'Subscription request submitted successfully',
            201
        );
    }
}
