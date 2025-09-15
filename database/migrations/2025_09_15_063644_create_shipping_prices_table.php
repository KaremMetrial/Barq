<?php

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
        Schema::create('shipping_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_price', 10, 3);
            $table->decimal('max_price', 10, 3);
            $table->decimal('per_km_price',10,3);
            $table->decimal('max_cod_price',10,3);
            // $table->boolean('enable_dynamic_pricing')->default(false);
            $table->boolean('enable_cod')->default(false);
            $table->timestamps();

            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();

            $table->unique(['zone_id','vehicle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_prices');
    }
};
