<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Transportation Announcement
        Announcement::create([
            'title' => 'Spring 2026 Bus Routes',
            'content' => 'New bus schedules are now active. Check the Routes tab for updated timings.',
            'type' => 'info',
            'is_active' => true,
            'expires_at' => Carbon::now()->addMonths(4),
            'created_at' => Carbon::now(),
        ]);

        // 2. ID Card Announcement
        Announcement::create([
            'title' => 'Digital ID Card Renewal',
            'content' => 'You can now renew lost or expired ID cards directly through this portal.',
            'type' => 'success',
            'is_active' => true,
            'expires_at' => Carbon::now()->addMonths(3),
            'created_at' => Carbon::now(),
        ]);

        // 3. System Maintenance
        Announcement::create([
            'title' => 'System Maintenance',
            'content' => 'Scheduled maintenance on Feb 20th, 02:00 AM - 04:00 AM. Services will be unavailable.',
            'type' => 'warning',
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(15),
            'created_at' => Carbon::now(),
        ]);
        
        // 4. Payment Deadline
        Announcement::create([
            'title' => 'Transport Payment Deadline',
            'content' => 'Final deadline for Spring payments is Feb 15th. Unpaid accounts will be suspended.',
            'type' => 'danger',
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now(),
        ]);
    }
}
