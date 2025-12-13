<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Order\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CourierReceiptUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $courier;
    protected $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required basic data
        $country = \Modules\Country\Models\Country::create([
            'code' => 'EG',
            'phone_code' => '20',
            'name' => 'Egypt',
            'currency_symbol' => 'EGP',
            'flag' => '🇪🇬',
        ]);

        $governorate = \Modules\Governorate\Models\Governorate::create([
            'country_id' => $country->id,
            'name' => 'Cairo',
        ]);

        $city = \Modules\City\Models\City::create([
            'governorate_id' => $governorate->id,
            'name' => 'Cairo',
        ]);

        $zone = \Modules\Zone\Models\Zone::create([
            'name' => 'Downtown Cairo',
            'area' => DB::raw("ST_GeomFromText('POINT(30.0444 31.2357)')"),
        ]);

        $category = \Modules\Category\Models\Category::create([
            'name' => 'Food',
            'image' => 'test.jpg',
        ]);

        $store = \Modules\Store\Models\Store::create([
            'name' => 'Test Store',
            'phone' => '123456789',
            'email' => 'store@test.com',
            'address' => 'Test Address',
            'logo' => 'test.jpg',
            'commercial_registration' => '12345',
            'tax_number' => '67890',
        ]);

        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'phone' => '123456789',
            'password' => bcrypt('password'),
        ]);

        // Create test order
        $order = Order::create([
            'order_number' => 'ORD-001',
            'user_id' => $user->id,
            'store_id' => $store->id,
            'total_amount' => 100.00,
            'delivery_fee' => 10.00,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'type' => \App\Enums\OrderTypeEnum::DELIVER,
        ]);

        // Create test courier
        $this->courier = Couier::create([
            'name' => 'Test Courier',
            'email' => 'courier@test.com',
            'phone' => '987654321',
            'password' => bcrypt('password'),
            'store_id' => $store->id,
            'status' => 'active',
            'available_status' => 'available',
        ]);
        $this->courier->assignRole('courier');

        // Create test assignment
        $this->assignment = CourierOrderAssignment::create([
            'courier_id' => $this->courier->id,
            'order_id' => $order->id,
            'status' => 'accepted',
            'assigned_at' => now(),
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
        ]);
    }

    /** @test */
    public function courier_can_upload_pickup_receipt()
    {
        // Create fake image file
        $file = UploadedFile::fake()->image('receipt.jpg', 640, 480);

        $data = [
            'file' => $file,
            'type' => 'pickup_receipt',
            'metadata' => [
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'timestamp' => now()->toISOString(),
            ],
        ];

        // Act as courier
        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-receipt", $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
                'receipt' => [
                    'id',
                    'file_name',
                    'url',
                    'file_size_human',
                    'type',
                    'uploaded_at',
                ]
            ]);

        // Assert file was uploaded
        $this->assertDatabaseHas('order_receipts', [
            'assignment_id' => $this->assignment->id,
            'type' => 'pickup_receipt',
        ]);
    }

    /** @test */
    public function courier_can_upload_delivery_proof()
    {
        // Update assignment status to in_transit
        $this->assignment->update(['status' => 'in_transit']);

        // Create fake image file
        $file = UploadedFile::fake()->image('delivery_proof.jpg', 640, 480);

        $data = [
            'file' => $file,
            'type' => 'delivery_proof',
            'metadata' => [
                'latitude' => 30.0444,
                'longitude' => 31.2357,
            ],
        ];

        // Act as courier
        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-delivery-proof", $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Delivery proof uploaded successfully']);

        // Assert file was uploaded
        $this->assertDatabaseHas('order_receipts', [
            'assignment_id' => $this->assignment->id,
            'type' => 'delivery_proof',
        ]);
    }

    /** @test */
    public function courier_cannot_upload_pickup_receipt_after_already_uploaded()
    {
        // Upload first receipt
        $file1 = UploadedFile::fake()->image('receipt1.jpg');
        $data1 = [
            'file' => $file1,
            'type' => 'pickup_receipt',
        ];

        $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-receipt", $data1);

        // Try to upload second receipt of same type
        $file2 = UploadedFile::fake()->image('receipt2.jpg');
        $data2 = [
            'file' => $file2,
            'type' => 'pickup_receipt',
        ];

        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-receipt", $data2);

        // Should fail
        $response->assertStatus(400);
    }

    /** @test */
    public function courier_can_get_full_order_details()
    {
        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/full-details");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data"order' => [
                    'assignment_id',
                    'status',
                    'customer',
                    'store',
                    'order',
                    'products',
                    'payment',
                    'delivery',
                    'upload_status',
                    'receipts',
                ],
            ]);
    }

    /** @test */
    public function courier_cannot_upload_to_another_couriers_assignment()
    {
        // Create another courier
        $store = \Modules\Store\Models\Store::first();
        $anotherCourier = Couier::create([
            'name' => 'Another Courier',
            'email' => 'another@test.com',
            'phone' => '111111111',
            'password' => bcrypt('password'),
            'store_id' => $store->id,
            'status' => 'active',
            'available_status' => 'available',
        ]);
        $anotherCourier->assignRole('courier');

        $file = UploadedFile::fake()->image('receipt.jpg');

        $data = [
            'file' => $file,
            'type' => 'pickup_receipt',
        ];

        // Try to upload to assignment that belongs to different courier
        $response = $this->actingAs($anotherCourier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-receipt", $data);

        $response->assertStatus(400); // Should be forbidden
    }

    /** @test */
    public function invalid_file_type_is_rejected()
    {
        // Create a fake PDF file (not allowed)
        $file = UploadedFile::fake()->create('receipt.pdf', 1024, 'application/pdf');

        $data = [
            'file' => $file,
            'type' => 'pickup_receipt',
        ];

        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/courier/orders/assignments/{$this->assignment->id}/upload-receipt", $data);

        $response->assertStatus(422) // Validation error
            ->assertJsonValidationErrors(['file']);
    }
}
