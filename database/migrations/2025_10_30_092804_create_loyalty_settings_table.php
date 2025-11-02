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
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->decimal('points_per_currency', 5, 2)->default(1.00); // Points earned per 1 currency unit
            $table->decimal('redemption_rate', 5, 2)->default(0.01); // Currency value per point
            $table->integer('points_expiry_days')->default(365); // Days until points expire
            $table->decimal('minimum_redemption_points', 10, 2)->default(100); // Minimum points to redeem
            $table->decimal('maximum_redemption_percentage', 5, 2)->default(50); // Max % of order that can be paid with points
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
