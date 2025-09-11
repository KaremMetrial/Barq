<?php

use App\Enums\ObjectTypeEnum;
use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount_amount', 8, 3);
            $table->string('discount_type')->default(SaleTypeEnum::PERCENTAGE->value);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->decimal('minimum_order_amount', 10, 3);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('coupon_type')->default(CouponTypeEnum::REGULAR->value);
            $table->string('object_type')->default(ObjectTypeEnum::GENERAL->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
