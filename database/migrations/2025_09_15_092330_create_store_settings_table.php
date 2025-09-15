<?php

use App\Enums\DeliveryTypeUnitEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('orders_enabled')->default(false);
            $table->boolean('delivery_service_enabled')->default(false);
            $table->boolean('external_pickup_enabled')->default(false);
            $table->string('product_classification')->nullable();
            $table->boolean('self_delivery_enabled')->default(false);
            $table->boolean('free_delivery_enabled')->default(false);
            $table->decimal('minimum_order_amount', 10, 3)->nullable();
            $table->unsignedInteger('delivery_time_min')->nullable();
            $table->unsignedInteger('delivery_time_max')->nullable();
            $table->string('delivery_type_unit')->default(DeliveryTypeUnitEnum::MINUTE->value);
            $table->decimal('tax_rate', 5, 3)->nullable();
            $table->unsignedInteger('order_interval_time')->nullable();
            $table->timestamps();

            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
