<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Couier\Models\ShiftTemplate;
use Modules\Couier\Models\ShiftTemplateDay;
use Modules\Couier\Models\CouierShift;
use Modules\Couier\Models\Couier;
use Modules\Store\Models\Store;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->info('No stores found. Please run StoreSeeder first.');
            return;
        }

        // Seed Shift Templates
        $this->seedShiftTemplates($faker, $stores);

        // Seed Courier Shifts
        $this->seedCourierShifts($faker);
    }

    private function seedShiftTemplates($faker, Collection $stores): void
    {
        $templateConfigs = [
            [
                'name' => 'Morning Shift (8 AM - 5 PM)',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_duration' => 90 // 1.5 hours
            ],
            [
                'name' => 'Evening Shift (4 PM - 11 PM)',
                'start_time' => '16:00:00',
                'end_time' => '23:00:00',
                'break_duration' => 60
            ],
            [
                'name' => 'Night Shift (10 PM - 7 AM)',
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'break_duration' => 60
            ],
            [
                'name' => 'Weekend Long Shift (9 AM - 8 PM)',
                'start_time' => '09:00:00',
                'end_time' => '20:00:00',
                'break_duration' => 120
            ],
            [
                'name' => 'Quick Shift (12 PM - 6 PM)',
                'start_time' => '12:00:00',
                'end_time' => '18:00:00',
                'break_duration' => 30
            ],
            [
                'name' => 'Flexible Shift (10 AM - 7 PM)',
                'start_time' => '10:00:00',
                'end_time' => '19:00:00',
                'break_duration' => 60
            ]
        ];

        foreach ($stores as $store) {
            // Create 3-4 templates per store
            $selectedIndices = array_rand($templateConfigs, mt_rand(3, 4));
            $selectedTemplates = is_array($selectedIndices) ? $selectedIndices : [$selectedIndices];

            foreach ($selectedTemplates as $index) {
                $template = ShiftTemplate::create([
                    'name' => $templateConfigs[$index]['name'],
                    'is_active' => mt_rand(0, 100) < 90, // 90% chance of being active
                    'is_flexible' => mt_rand(0, 100) < 30, // 30% are flexible
                    'store_id' => $store->id
                ]);

                // Create schedule for weekdays (Monday-Friday by default)
                $this->createTemplateSchedule($faker, $template, $templateConfigs[$index]);
            }
        }
    }

    private function createTemplateSchedule($faker, ShiftTemplate $template, array $config): void
    {
        $weekdays = [1, 2, 3, 4, 5]; // Monday to Friday
        $workingDaysCount = mt_rand(4, 5);
        shuffle($weekdays);
        $workingDays = array_slice($weekdays, 0, $workingDaysCount); // 4-5 working days

        foreach ($weekdays as $dayOfWeek) {
            ShiftTemplateDay::create([
                'shift_template_id' => $template->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => in_array($dayOfWeek, $workingDays) ? $config['start_time'] : '00:00:00',
                'end_time' => in_array($dayOfWeek, $workingDays) ? $config['end_time'] : '00:00:00',
                'break_duration' => in_array($dayOfWeek, $workingDays) ? $config['break_duration'] : 0,
                'is_off_day' => !in_array($dayOfWeek, $workingDays)
            ]);
        }
    }

    private function seedCourierShifts($faker): void
    {
        $couriers = Couier::where('status', 'active')->get();
        $templates = ShiftTemplate::where('is_active', true)->get();

        if ($couriers->isEmpty() || $templates->isEmpty()) {
            $this->command->info('No active couriers or templates found. Please run CouierSeeder first.');
            return;
        }

        // Create various shift scenarios for each courier
        foreach ($couriers as $courier) {
            // Each courier gets 10-15 shifts (mix of completed and active)
            $shiftCount = mt_rand(10, 15);

            for ($i = 0; $i < $shiftCount; $i++) {
                $template = $templates->random();
                $this->createCourierShift($faker, $courier, $template);
            }
        }

        // Ensure some couriers have active shifts (currently working)
        $activeCourierCount = min($couriers->count(), 3); // Up to 3 couriers working now
        $activeCouriers = $couriers->random($activeCourierCount);

        foreach ($activeCouriers as $courier) {
            $template = $templates->where('store_id', $courier->store_id)->first()
                        ?? $templates->first();

            $this->createActiveCourierShift($faker, $courier, $template);
        }
    }

    private function createCourierShift($faker, Couier $courier, ShiftTemplate $template): void
    {
        // Random date in the past 30 days
        $shiftDate = $faker->dateTimeBetween('-30 days', '-1 day');

        // Get template day schedule or use defaults
        $shiftDateCarbon = Carbon::parse($shiftDate);
        $templateDay = $template->days()->where('day_of_week', $shiftDateCarbon->dayOfWeek)->first();
        $defaultStartTime = '09:00:00';
        $defaultEndTime = '17:00:00';

        $startTimeStr = $templateDay && !$templateDay->is_off_day ? $templateDay->start_time : $defaultStartTime;
        $endTimeStr = $templateDay && !$templateDay->is_off_day ? $templateDay->end_time : $defaultEndTime;

        $startTime = Carbon::parse($startTimeStr);
        $endTime = Carbon::parse($endTimeStr);

        // Add some variation (±1 hour)
        $actualStart = $this->addTimeVariation($startTime, mt_rand(-30, 30));
        $expectedEnd = $this->addTimeVariation($endTime, mt_rand(-15, 15));
        $actualEnd = $this->addTimeVariation($expectedEnd, mt_rand(-60, 60));

        // Create shift record
        $shift = CouierShift::create([
            'start_time' => $shiftDate->setTime(
                $actualStart->hour,
                $actualStart->minute,
                $actualStart->second
            ),
            'end_time' => $shiftDate->setTime(
                $actualEnd->hour,
                $actualEnd->minute,
                $actualEnd->second
            ),
            'expected_end_time' => $shiftDate->setTime(
                $expectedEnd->hour,
                $expectedEnd->minute,
                $expectedEnd->second
            ),
            'is_open' => false, // Historical shifts are closed
            'couier_id' => $courier->id,
            'shift_template_id' => $template->id,
            'total_orders' => mt_rand(5, 25),
            'total_earnings' => round(mt_rand(50, 30000) / 100, 2),
            'notes' => mt_rand(1, 10) > 7 ? 'Sample shift notes' : null // 30% chance of having notes
        ]);

        // Add overtime if applicable
        if ($actualEnd->isAfter($expectedEnd)) {
            $overtimeMinutes = $actualEnd->diffInMinutes($expectedEnd);
            $shift->update([
                'overtime_minutes' => $overtimeMinutes,
                'overtime_pay' => round($overtimeMinutes / 60 * round(mt_rand(1000, 2000) / 100, 1), 2)
            ]);
        }

        // Add break data (60% chance of taking break)
        if (mt_rand(1, 10) <= 6) {
            $breakStart = $this->calculateBreakStartTime($shift);
            $breakDuration = mt_rand(30, 90); // 30-90 minutes
            $breakEnd = $breakStart->copy()->addMinutes($breakDuration);

            $shift->update([
                'break_start' => $breakStart,
                'break_end' => $breakEnd
            ]);
        }
    }

    private function createActiveCourierShift($faker, Couier $courier, ShiftTemplate $template): void
    {
        $startTime = Carbon::now()->subHours(mt_rand(1, 8));

        CouierShift::create([
            'start_time' => $startTime,
            'expected_end_time' => $startTime->copy()->addHours(8),
            'is_open' => true, // Currently active
            'couier_id' => $courier->id,
            'shift_template_id' => $template->id,
            'total_orders' => mt_rand(1, 10), // Orders so far today
            'total_earnings' => round(mt_rand(10, 15000) / 100, 2) // Earnings so far
        ]);
    }

    private function addTimeVariation(Carbon $time, int $minutes): Carbon
    {
        return $time->copy()->addMinutes($minutes);
    }

    private function calculateBreakStartTime(CouierShift $shift): Carbon
    {
        $startTime = $shift->start_time;
        $endTime = $shift->end_time ?? $shift->expected_end_time;

        // Break should be roughly in the middle of the shift
        $shiftDuration = $startTime->diffInMinutes($endTime);
        $breakStartMinutes = $shiftDuration * round(mt_rand(30, 70) / 100, 2); // 0.3 to 0.7
        $breakStartMinutes = max(60, min($shiftDuration - 60, $breakStartMinutes)); // Between 1-2 hours from start, but not too close to end

        return $startTime->copy()->addMinutes($breakStartMinutes);
    }
}
