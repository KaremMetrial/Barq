<?php

namespace Modules\Vendor\Tests\Feature;

use Tests\TestCase;
use Modules\Vendor\Models\Vendor;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;
use Modules\Balance\Models\Balance;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class VendorReportTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor;
    protected $store;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a store
        $this->store = Store::create([
            'name' => 'Test Store',
            'description' => 'Test Description',
            'is_active' => true
        ]);

        // Create a vendor
        $this->vendor = Vendor::create([
            'first_name' => 'Test',
            'last_name' => 'Vendor',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'is_active' => true,
            'store_id' => $this->store->id
        ]);

        // Create balance for vendor
        Balance::create([
            'balanceable_id' => $this->vendor->id,
            'balanceable_type' => Vendor::class,
            'total_balance' => 25000.000,
            'available_balance' => 16000.000,
            'pending_balance' => 9000.000
        ]);

        // Create some test orders
        Order::create([
            'order_number' => 'ORD-001',
            'type' => 'deliver',
            'status' => 'delivered',
            'total_amount' => 2500.500,
            'store_id' => $this->store->id,
            'user_id' => 1,
            'created_at' => now()
        ]);

        Order::create([
            'order_number' => 'ORD-002',
            'type' => 'deliver',
            'status' => 'delivered',
            'total_amount' => 1500.750,
            'store_id' => $this->store->id,
            'user_id' => 1,
            'created_at' => now()->subHours(2)
        ]);

        // Create some transactions
        Transaction::create([
            'user_id' => $this->vendor->id,
            'transactionable_type' => get_class($this->vendor),
            'transactionable_id' => $this->vendor->id,
            'type' => 'withdrawal',
            'amount' => 20000.000,
            'currency' => 'KWD',
            'description' => 'Sحب من المحفظة',
            'created_at' => now()->subDays(1)
        ]);

        Transaction::create([
            'user_id' => $this->vendor->id,
            'transactionable_type' => get_class($this->vendor),
            'transactionable_id' => $this->vendor->id,
            'type' => 'commission',
            'amount' => 5000.000,
            'currency' => 'KWD',
            'description' => 'دفع عمولة شهر فبراير',
            'created_at' => now()->subDays(2)
        ]);

        // Authenticate the vendor
        $this->token = $this->vendor->createToken('test-token', ['vendor'])->plainTextToken;
    }

    public function test_vendor_can_access_reports_endpoint()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/v1/vendor/reports');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'operational_performance' => [
                    'daily_metrics' => [
                        'total_sales',
                        'order_count',
                        'average_order_value',
                        'peak_hours'
                    ],
                    'weekly_sales_chart',
                    'peak_hour_analysis'
                ],
                'financial_information' => [
                    'wallet_balance',
                    'commissions'
                ],
                'wallet_data',
                'transactions'
            ]
        ]);
    }

    public function test_reports_endpoint_returns_correct_data_structure()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/v1/vendor/reports');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Check operational performance structure
        $this->assertArrayHasKey('operational_performance', $data);
        $this->assertArrayHasKey('daily_metrics', $data['operational_performance']);
        $this->assertArrayHasKey('weekly_sales_chart', $data['operational_performance']);
        $this->assertArrayHasKey('peak_hour_analysis', $data['operational_performance']);

        // Check financial information structure
        $this->assertArrayHasKey('financial_information', $data);
        $this->assertArrayHasKey('wallet_balance', $data['financial_information']);
        $this->assertArrayHasKey('commissions', $data['financial_information']);

        // Check wallet data structure
        $this->assertArrayHasKey('wallet_data', $data);
        $this->assertArrayHasKey('total_balance', $data['wallet_data']);
        $this->assertArrayHasKey('available_for_withdrawal', $data['wallet_data']);

        // Check transactions structure
        $this->assertArrayHasKey('transactions', $data);
        $this->assertIsArray($data['transactions']);
    }

    public function test_reports_endpoint_returns_correct_values()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/v1/vendor/reports');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Check wallet balance values
        $this->assertEquals(25000.0, $data['wallet_data']['total_balance']);
        $this->assertEquals(16000.0, $data['wallet_data']['available_for_withdrawal']);

        // Check that we have transactions
        $this->assertCount(2, $data['transactions']);

        // Check first transaction
        $this->assertEquals('withdrawal', $data['transactions'][0]['type']);
        $this->assertEquals(20000.0, $data['transactions'][0]['amount']);

        // Check second transaction
        $this->assertEquals('commission', $data['transactions'][1]['type']);
        $this->assertEquals(5000.0, $data['transactions'][1]['amount']);
    }

    public function test_unauthenticated_vendor_cannot_access_reports()
    {
        $response = $this->get('/api/v1/vendor/reports');
        $response->assertStatus(401);
    }

    public function test_reports_endpoint_with_date_range()
    {
        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get("/api/v1/vendor/reports?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'operational_performance' => [
                    'daily_metrics',
                    'weekly_sales_chart',
                    'peak_hour_analysis'
                ],
                'financial_information',
                'wallet_data',
                'transactions'
            ]
        ]);
    }
}
