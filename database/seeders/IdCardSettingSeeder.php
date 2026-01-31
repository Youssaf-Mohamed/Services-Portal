<?php

namespace Database\Seeders;

use App\Models\IdCardSetting;
use Illuminate\Database\Seeder;

class IdCardSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IdCardSetting::updateOrCreate(
            ['id' => 1],
            [
                'payment_account_number' => '1234567890',
                'payment_account_name' => 'University Card Services',
                'payment_instructions' => 'Please transfer the exact amount to the account above and upload a screenshot of the transfer confirmation.',
                'service_enabled' => true,
            ]
        );
    }
}
