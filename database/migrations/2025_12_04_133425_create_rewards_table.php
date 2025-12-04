<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\RewardType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default(RewardType::WALLET);
            $table->integer('points_cost');
            $table->decimal('value_amount', 10, 2);
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('usage_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
