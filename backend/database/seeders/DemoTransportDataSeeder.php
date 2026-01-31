<?php

namespace Database\Seeders;

use App\Models\BusRoute;
use App\Models\BusStop;
use App\Models\BusScheduleSlot;
use Illuminate\Database\Seeder;

class DemoTransportDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo routes, stops, and schedule slots. Idempotent.
     */
    public function run(): void
    {
        // Create stops
        $stops = [
            ['name_ar' => 'محطة المترو - المرج', 'name_en' => 'Metro Station - El Marg', 'lat' => 30.1704, 'lng' => 31.3358],
            ['name_ar' => 'ميدان رمسيس', 'name_en' => 'Ramses Square', 'lat' => 30.0626, 'lng' => 31.2497],
            ['name_ar' => 'الجامعة - المدخل الرئيسي', 'name_en' => 'University - Main Gate', 'lat' => 30.0444, 'lng' => 31.2357],
            ['name_ar' => 'مدينة نصر - عباس العقاد', 'name_en' => 'Nasr City - Abbas El Akkad', 'lat' => 30.0561, 'lng' => 31.3378],
            ['name_ar' => 'مدينتي - المركز التجاري', 'name_en' => 'Madinaty - Mall', 'lat' => 30.1035, 'lng' => 31.6494],
            ['name_ar' => 'الرحاب - المدخل الأول', 'name_en' => 'Rehab - Gate 1', 'lat' => 30.0583, 'lng' => 31.4981],
            ['name_ar' => 'التجمع الخامس - الجامعة الأمريكية', 'name_en' => 'New Cairo - AUC', 'lat' => 30.0131, 'lng' => 31.5008],
        ];

        $createdStops = [];
        foreach ($stops as $stopData) {
            $stop = BusStop::firstOrCreate(
                ['name_ar' => $stopData['name_ar']],
                $stopData
            );
            $createdStops[] = $stop;
        }

        // Create routes
        $routes = [
            [
                'name_ar' => 'خط المرج - الجامعة',
                'name_en' => 'El Marg - University Route',
                'price_one_way' => 15.00,
                'monthly_discount_percent' => 10,
                'term_discount_percent' => 20,
                'active' => true,
                'stops' => [0, 1, 2], // Metro, Ramses, University
            ],
            [
                'name_ar' => 'خط التجمع الخامس - الجامعة',
                'name_en' => 'New Cairo - University Route',
                'price_one_way' => 20.00,
                'monthly_discount_percent' => 15,
                'term_discount_percent' => 25,
                'active' => true,
                'stops' => [6, 5, 3, 2], // AUC, Rehab, Nasr City, University
            ],
        ];

        foreach ($routes as $routeData) {
            $stopIndexes = $routeData['stops'];
            unset($routeData['stops']);

            $route = BusRoute::firstOrCreate(
                ['name_ar' => $routeData['name_ar']],
                $routeData
            );

            // Attach stops with sort_order
            $syncData = [];
            foreach ($stopIndexes as $index => $stopIndex) {
                $syncData[$createdStops[$stopIndex]->id] = ['sort_order' => $index + 1];
            }
            $route->stops()->sync($syncData);

            // Create schedule slots for this route
            $this->createScheduleSlots($route);
        }

        $this->command->info('Demo transport data seeded successfully.');
    }

    /**
     * Create schedule slots for a route.
     * 
     * Strategy: Option A - round_trip only for simplicity
     * - 5 days (Mon-Fri, days 1-5 where 0=Sunday, 6=Saturday)
     * - 2 times per day (07:30, 14:30)
     * - 1 direction (round_trip)
     * = 10 slots per route
     * 
     * Total for 2 routes = 20 slots
     */
    private function createScheduleSlots(BusRoute $route): void
    {
        $times = ['07:30:00', '14:30:00'];
        $daysOfWeek = [1, 2, 3, 4, 5]; // Monday to Friday (0=Sunday, 6=Saturday)
        $direction = 'round_trip'; // Option A: Only round_trip

        foreach ($daysOfWeek as $day) {
            foreach ($times as $time) {
                BusScheduleSlot::firstOrCreate(
                    [
                        'route_id' => $route->id,
                        'day_of_week' => $day,
                        'direction' => $direction,
                        'time' => $time,
                    ],
                    [
                        'capacity' => 40,
                        'active' => true,
                    ]
                );
            }
        }
    }
}
