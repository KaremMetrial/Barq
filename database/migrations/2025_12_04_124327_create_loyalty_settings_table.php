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
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->decimal('earn_rate', 5, 2)->default(0.10);
            $table->decimal('min_order_for_earn', 10, 2)->default(10.00);
            $table->unsignedInteger('referral_points')->default(200);
            $table->unsignedInteger('rating_points')->default(30);
            $table->timestamps();
            $table->unique('country_id');
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
