<?php

namespace App\Services;

use App\Enums\Transport\PlanType;
use App\Models\BusRoute;
use App\Models\TransportPlan;
use App\Models\TransportSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransportPricingService
{
    // Valid selectable days (Sat-Thu only, Friday excluded)
    private const VALID_DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

    /**
     * Calculate subscription pricing for a given plan type, route, and settings.
     * LEGACY METHOD - for backward compatibility with existing subscriptions
     * 
     * @param string|PlanType $planType Plan type ('monthly' or 'term')
     * @param BusRoute $route The bus route with pricing info
     * @param TransportSetting $settings Transport settings (days_per_week, weeks_in_month, weeks_in_term)
     * @return array Pricing breakdown
     */
    public function calculate(string|PlanType $planType, BusRoute $route, TransportSetting $settings): array
    {
        // Normalize plan type to string
        $planTypeValue = $planType instanceof PlanType ? $planType->value : $planType;
        
        // Base one-way price
        $priceOneWay = (float) $route->price_one_way;
        
        // Round trip = one-way * 2
        $dailyRoundTrip = $priceOneWay * 2;
        
        // Get settings values
        $daysPerWeek = $settings->days_per_week;
        $weeksInMonth = $settings->weeks_in_month ?? 4;
        $weeksInTerm = $settings->weeks_in_term;
        
        // Calculate base total based on plan type
        if ($planTypeValue === PlanType::MONTHLY->value || $planTypeValue === 'monthly') {
            $computedBaseTotal = $dailyRoundTrip * $daysPerWeek * $weeksInMonth;
            $discountPercent = (int) $route->monthly_discount_percent;
        } else {
            $computedBaseTotal = $dailyRoundTrip * $daysPerWeek * $weeksInTerm;
            $discountPercent = (int) $route->term_discount_percent;
        }
        
        // Calculate discount and final amount
        $discountAmount = $computedBaseTotal * ($discountPercent / 100);
        $finalAmount = $computedBaseTotal - $discountAmount;
        
        return [
            'base_price_one_way' => round($priceOneWay, 2),
            'daily_round_trip' => round($dailyRoundTrip, 2),
            'computed_base_total' => round($computedBaseTotal, 2),
            'discount_percent' => $discountPercent,
            'discount_amount' => round($discountAmount, 2),
            'final_amount' => round($finalAmount, 2),
            'breakdown' => [
                'days_per_week' => $daysPerWeek,
                'weeks_in_month' => $weeksInMonth,
                'weeks_in_term' => $weeksInTerm,
                'direction' => 'round_trip',
            ],
            'plan_type' => $planTypeValue,
        ];
    }

    /**
     * Validate selected days against plan requirements.
     *
     * @throws ValidationException
     */
    public function validateSelectedDays(TransportPlan $plan, array $selectedDays): void
    {
        $validator = Validator::make(['selected_days' => $selectedDays], [
            'selected_days' => 'required|array',
            'selected_days.*' => 'string|in:' . implode(',', self::VALID_DAYS),
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check for duplicates
        if (count($selectedDays) !== count(array_unique($selectedDays))) {
            throw ValidationException::withMessages([
                'selected_days' => ['Duplicate days are not allowed.']
            ]);
        }

        // Check exact count
        if (count($selectedDays) !== $plan->allowed_days_per_week) {
            throw ValidationException::withMessages([
                'selected_days' => [
                    "You must select exactly {$plan->allowed_days_per_week} days for this plan."
                ]
            ]);
        }

        // Ensure Friday is not included
        if (in_array('friday', $selectedDays)) {
            throw ValidationException::withMessages([
                'selected_days' => ['Friday is not a selectable day.']
            ]);
        }
    }

    /**
     * Calculate expected amount for a subscription request with plan and selected days.
     *
     * @param BusRoute $route
     * @param TransportPlan $plan
     * @param array $selectedDays
     * @param TransportSetting $settings
     * @return array ['amount' => float, 'breakdown' => array]
     */
    public function calculateWithPlan(
        BusRoute $route,
        TransportPlan $plan,
        array $selectedDays,
        TransportSetting $settings
    ): array {
        // Daily cost (always round trip)
        $dailyCost = $route->price_one_way * 2;

        // Number of selected days
        $selectedDaysCount = count($selectedDays);

        // Determine weeks based on plan type
        if ($plan->plan_type === 'monthly') {
            $weeks = $settings->weeks_in_month ?? 4;
            $discountPercent = $route->monthly_discount_percent;
        } else { // term
            $weeks = $settings->weeks_in_term;
            $discountPercent = $route->term_discount_percent;
        }

        // Calculate total before discount
        $totalBeforeDiscount = $dailyCost * $selectedDaysCount * $weeks;

        // Apply discount
        $discountAmount = $totalBeforeDiscount * ($discountPercent / 100);
        $finalAmount = $totalBeforeDiscount - $discountAmount;

        return [
            'amount' => round($finalAmount, 2),
            'breakdown' => [
                'daily_cost' => round($dailyCost, 2),
                'selected_days_count' => $selectedDaysCount,
                'weeks' => $weeks,
                'total_before_discount' => round($totalBeforeDiscount, 2),
                'discount_percent' => $discountPercent,
                'discount_amount' => round($discountAmount, 2),
                'final_amount' => round($finalAmount, 2),
                'plan_name_ar' => $plan->name_ar,
                'plan_name_en' => $plan->name_en,
                'allowed_days_per_week' => $plan->allowed_days_per_week,
            ],
        ];
    }

    /**
     * Verify that the paid amount matches expected amount.
     *
     * @throws ValidationException
     */
    public function verifyPaymentAmount(float $expectedAmount, float $paidAmount): void
    {
        $difference = abs($paidAmount - $expectedAmount);
        
        if ($difference > 0.01) {
            throw ValidationException::withMessages([
                'amount_paid' => [
                    "Payment amount mismatch. Expected: {$expectedAmount}, Paid: {$paidAmount}"
                ]
            ]);
        }
    }

    /**
     * Calculate subscription end date based on plan type.
     *
     * @param string $planType (monthly or term)
     * @param \DateTime|null $startDate
     * @return \DateTime
     */
    public function calculateEndDate(string $planType, ?\DateTime $startDate = null): \DateTime
    {
        $startDate = $startDate ?? now();
        
        if ($planType === 'monthly') {
            return (clone $startDate)->modify('+1 month');
        } else { // term = 3 months for display consistency
            return (clone $startDate)->modify('+3 months');
        }
    }

    /**
     * Get valid selectable days.
     */
    public function getValidDays(): array
    {
        return self::VALID_DAYS;
    }
}

