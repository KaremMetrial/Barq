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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('food_quality_rating')->nullable();
            $table->unsignedTinyInteger('delivery_speed_rating')->nullable();
            $table->unsignedTinyInteger('order_execution_speed_rating')->nullable();
            $table->unsignedTinyInteger('product_quality_rating')->nullable();
            $table->unsignedTinyInteger('shopping_experience_rating')->nullable();
            $table->unsignedTinyInteger('overall_experience_rating')->nullable();
            $table->unsignedTinyInteger('delivery_driver_rating')->nullable();
            $table->unsignedTinyInteger('delivery_condition_rating')->nullable();
            $table->unsignedTinyInteger('match_price_rating')->nullable();
            $table->string('image')->nullable();

            $table->timestamps();
            // $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->morphs('reviewable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
