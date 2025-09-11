<?php

use App\Enums\PlanBillingCycleEnum;
use App\Enums\PlanTypeEnum;
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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->decimal('price',10,3);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('vehicle_limit')->default(0);
            $table->unsignedTinyInteger('order_limit')->default(0);
            $table->string('billing_cycle')->default(PlanBillingCycleEnum::MONTHLY);
            $table->string('type')->default(PlanTypeEnum::SUBSCRIPTION->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
