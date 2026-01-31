<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample payment methods if they don't exist.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Vodafone Cash',
                'account_number' => '01012345678',
                'instructions' => 'يرجى إرسال المبلغ إلى رقم فودافون كاش المذكور والاحتفاظ بإيصال الدفع.',
                'active' => true,
            ],
            [
                'name' => 'Bank Transfer',
                'account_number' => 'EG380001234567890123456789',
                'instructions' => 'Account Name: University Transport Services. Bank: National Bank of Egypt. Please include your student ID in transfer notes.',
                'active' => true,
            ],
        ];

        foreach ($methods as $methodData) {
            PaymentMethod::firstOrCreate(
                ['name' => $methodData['name']],
                $methodData
            );
        }

        $this->command->info('Payment methods seeded successfully.');
    }
}
