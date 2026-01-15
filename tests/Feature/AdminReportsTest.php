<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Admin\Models\Admin;
use Modules\Order\Models\Order;
use App\Models\Transaction;
use Modules\Store\Models\Store;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_endpoint_returns_trade_and_percentage_values()
    {
        // Create test data
        $admin = Admin::factory()->create();
        $store = Store::factory()->create([
            'commission_type' => 'percentage',
            'commission_amount' => 10
        ]);

        // Create orders for current period
        Order::factory()->count(5)->create([
            'store_id' => $store->id,
            'total_amount' => 100,
            'status' => 'delivered',
            'created_at' => now()
        ]);

        // Create orders for previous period
        Order::factory()->count(3)->create([
            'store_id' => $store->id,
            'total_amount' => 50,
            'status' => 'delivered',
            'created_at' => now()->subDays(30)
        ]);

        // Create transactions for refunds
        Transaction::factory()->create([
            'type' => 'refund',
            'amount' => 20,
            'created_at' => now()
        ]);

        Transaction::factory()->create([
            'type' => 'refund',
            'amount' => 10,
            'created_at' => now()->subDays(30)
        ]);

        // Act as admin and call the endpoint
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reports');

        // Assert the response is successful
        $response->assertSuccessful();

        // Get the response data
        $data = $response->json();

        // Assert the structure contains trade and percentage for required metrics
        $this->assertArrayHasKey('trade', $data['metrics']['total_sales']);
        $this->assertArrayHasKey('percentage', $data['metrics']['total_sales']);

        $this->assertArrayHasKey('trade', $data['metrics']['total_orders']);
        $this->assertArrayHasKey('percentage', $data['metrics']['total_orders']);

        $this->assertArrayHasKey('trade', $data['metrics']['average_order_value']);
        $this->assertArrayHasKey('percentage', $data['metrics']['average_order_value']);

        $this->assertArrayHasKey('trade', $data['metrics']['commission_earned']);
        $this->assertArrayHasKey('percentage', $data['metrics']['commission_earned']);

        $this->assertArrayHasKey('trade', $data['metrics']['refunds']);
        $this->assertArrayHasKey('percentage', $data['metrics']['refunds']);

        // Verify the trade values are numeric
        $this->assertIsNumeric($data['metrics']['total_sales']['trade']);
        $this->assertIsNumeric($data['metrics']['total_orders']['trade']);
        $this->assertIsNumeric($data['metrics']['average_order_value']['trade']);
        $this->assertIsNumeric($data['metrics']['commission_earned']['trade']);
        $this->assertIsNumeric($data['metrics']['refunds']['trade']);

        // Verify the percentage values are numeric
        $this->assertIsNumeric($data['metrics']['total_sales']['percentage']);
        $this->assertIsNumeric($data['metrics']['total_orders']['percentage']);
        $this->assertIsNumeric($data['metrics']['average_order_value']['percentage']);
        $this->assertIsNumeric($data['metrics']['commission_earned']['percentage']);
        $this->assertIsNumeric($data['metrics']['refunds']['percentage']);

        // Verify percentage calculations make sense
        // Current period: 5 orders * 100 = 500 total sales
        // Previous period: 3 orders * 50 = 150 total sales
        // Expected percentage: ((500-150)/150)*100 = 233.33%
        $this->assertEqualsWithDelta(233.33, $data['metrics']['total_sales']['percentage'], 0.1);

        // Current period: 5 orders
        // Previous period: 3 orders
        // Expected percentage: ((5-3)/3)*100 = 66.67%
        $this->assertEqualsWithDelta(66.67, $data['metrics']['total_orders']['percentage'], 0.1);

        echo "Test passed! The reports endpoint now returns trade and percentage values for all required metrics.\n";
    }
}
