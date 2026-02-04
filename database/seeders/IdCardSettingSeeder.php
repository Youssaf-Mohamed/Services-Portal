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
                'payment_instructions' => 'يرجى تحويل المبلغ المطلوب بدقة إلى الحساب الموضح أعلاه وإرفاق صورة من إيصال التحويل.',
                'service_enabled' => true,
            ]
        );
    }
}
