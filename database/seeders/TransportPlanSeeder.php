<?php

namespace Database\Seeders;

use App\Models\TransportPlan;
use Illuminate\Database\Seeder;

class TransportPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Monthly plans
            [
                'name_ar' => 'خطة 3 أيام شهرية',
                'name_en' => '3 Days/Week Monthly Plan',
                'plan_type' => 'monthly',
                'allowed_days_per_week' => 3,
                'active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'خطة 4 أيام شهرية',
                'name_en' => '4 Days/Week Monthly Plan',
                'plan_type' => 'monthly',
                'allowed_days_per_week' => 4,
                'active' => true,
                'sort_order' => 2,
            ],
            // Term plans (3 months)
            [
                'name_ar' => 'خطة 3 أيام فصلية (3 أشهر)',
                'name_en' => '3 Days/Week Term Plan (3 months)',
                'plan_type' => 'term',
                'allowed_days_per_week' => 3,
                'active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'خطة 4 أيام فصلية (3 أشهر)',
                'name_en' => '4 Days/Week Term Plan (3 months)',
                'plan_type' => 'term',
                'allowed_days_per_week' => 4,
                'active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            TransportPlan::updateOrCreate(
                [
                    'plan_type' => $plan['plan_type'],
                    'allowed_days_per_week' => $plan['allowed_days_per_week'],
                ],
                $plan
            );
        }
    }
}
