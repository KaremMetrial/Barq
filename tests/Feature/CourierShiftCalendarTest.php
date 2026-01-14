<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CouierShift;
use Modules\Zone\Models\Zone;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class CourierShiftCalendarTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_calendar_data_without_errors()
    {
        // Create test data
        $courier = Couier::factory()->create();
        $zone = Zone::factory()->create();

        // Assign zone to courier
        $courier->zonesToCover()->attach($zone->id);

        // Create some shifts for the courier in January 2026
        $shifts = [
            [
                'start_time' => '2026-01-01 08:00:00',
                'end_time' => '2026-01-01 16:00:00',
                'is_open' => false,
                'couier_id' => $courier->id
            ],
            [
                'start_time' => '2026-01-15 09:00:00',
                'end_time' => '2026-01-15 17:00:00',
                'is_open' => false,
                'couier_id' => $courier->id
            ],
            [
                'start_time' => '2026-01-30 10:00:00',
                'end_time' => '2026-01-30 18:00:00',
                'is_open' => false,
                'couier_id' => $courier->id
            ]
        ];

        foreach ($shifts as $shiftData) {
            CouierShift::create($shiftData);
        }

        // Authenticate as the courier
        Sanctum::actingAs($courier, ['*']);

        // Make the request to the calendar endpoint
        $response = $this->get('/api/v1/courier/shifts/calendar-data?year=2026&month=1');

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response has the expected structure
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'year',
                'month',
                'days' => [
                    '*' => [
                        'date',
                        'day_of_week',
                        'day_name',
                        'has_shift',
                        'shift_status',
                        'shift_data'
                    ]
                ],
                'month_name'
            ]
        ]);

        // Assert we have data for all days in January (31 days)
        $responseData = $response->json('data.days');
        $this->assertCount(31, $responseData);

        // Assert that days with shifts have the correct data
        $day1 = collect($responseData)->firstWhere('date', '2026-01-01');
        $this->assertTrue($day1['has_shift']);
        $this->assertNotNull($day1['shift_data']);
        $this->assertArrayHasKey('zones', $day1['shift_data']);

        // Assert zones are from courier's zonesToCover
        $this->assertContains($zone->name, $day1['shift_data']['zones']);

        // Assert days without shifts have null shift_data
        $day2 = collect($responseData)->firstWhere('date', '2026-01-02');
        $this->assertFalse($day2['has_shift']);
        $this->assertNull($day2['shift_data']);
    }

    /** @test */
    public function it_uses_correct_column_names_in_query()
    {
        $courier = Couier::factory()->create();

        // Create a shift
        CouierShift::create([
            'start_time' => '2026-01-01 08:00:00',
            'end_time' => '2026-01-01 16:00:00',
            'is_open' => false,
            'couier_id' => $courier->id
        ]);

        Sanctum::actingAs($courier, ['*']);

        // This should NOT throw a "Column not found: date" error
        $response = $this->get('/api/v1/courier/shifts/calendar-data?year=2026&month=1');
        $response->assertStatus(200);

        // The response should contain our shift data
        $response->assertJsonFragment([
            'has_shift' => true
        ]);
    }

    /** @test */
    public function it_uses_courier_zones_instead_of_shift_zones()
    {
        $courier = Couier::factory()->create();
        $zone1 = Zone::factory()->create(['name' => 'Zone A']);
        $zone2 = Zone::factory()->create(['name' => 'Zone B']);

        // Assign zones to courier
        $courier->zonesToCover()->attach([$zone1->id, $zone2->id]);

        // Create a shift
        CouierShift::create([
            'start_time' => '2026-01-01 08:00:00',
            'end_time' => '2026-01-01 16:00:00',
            'is_open' => false,
            'couier_id' => $courier->id
        ]);

        Sanctum::actingAs($courier, ['*']);

        // This should NOT throw a "Call to undefined relationship [zones]" error
        $response = $this->get('/api/v1/courier/shifts/calendar-data?year=2026&month=1');
        $response->assertStatus(200);

        // The response should contain both zones from courier's zonesToCover
        $response->assertJsonFragment([
            'zones' => ['Zone A', 'Zone B']
        ]);
    }
}
