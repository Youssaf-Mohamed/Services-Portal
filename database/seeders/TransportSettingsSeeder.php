<?php

namespace Database\Seeders;

use App\Models\TransportSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransportSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ensures exactly one transport settings row exists with defaults.
     */
    public function run(): void
    {
        // Get or create the single settings row
        $settings = TransportSetting::first();

        if (!$settings) {
            // Get admin user if exists
            $adminUser = User::role('admin')->first();

            TransportSetting::create([
                'days_per_week' => 5,
                'weeks_in_month' => 4,
                'weeks_in_term' => 12,
                'updated_by' => $adminUser?->id,
            ]);

            $this->command->info('Transport settings created with default values.');
        } else {
            $this->command->info('Transport settings already exist. Skipping.');
        }
    }
}
