<?php

namespace Database\Seeders;

use App\Models\IdCardType;
use Illuminate\Database\Seeder;

class IdCardTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'lost',
                'name_ar' => 'بدل فاقد',
                'name_en' => 'Lost Card Replacement',
                'description_ar' => 'طلب إصدار بطاقة جديدة بدل البطاقة المفقودة',
                'description_en' => 'Request a new ID card to replace a lost one',
                'fee' => 150.00,
                'requires_photo' => false,
                'requires_description' => false,
                'active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'photo_change',
                'name_ar' => 'تغيير الصورة',
                'name_en' => 'Photo Change',
                'description_ar' => 'طلب تغيير الصورة الشخصية على البطاقة',
                'description_en' => 'Request to update your ID card photo',
                'fee' => 100.00,
                'requires_photo' => true,
                'requires_description' => false,
                'active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'damaged',
                'name_ar' => 'بطاقة تالفة / مشكلة',
                'name_en' => 'Damaged Card / Card Issue',
                'description_ar' => 'طلب إصدار بطاقة جديدة بدل البطاقة التالفة أو الإبلاغ عن مشكلة',
                'description_en' => 'Request a replacement for a damaged card or report an issue',
                'fee' => 150.00,
                'requires_photo' => false,
                'requires_description' => true,
                'active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($types as $type) {
            IdCardType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
