<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PromotionTypeEnum;
use App\Enums\PromotionSubTypeEnum;
use App\Enums\PromotionTargetTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(PromotionTypeEnum::DELIVERY);
            $table->string('sub_type')->default(PromotionSubTypeEnum::FREE_DELIVERY);
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('current_usage')->default(0);
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->foreignId('zone_id')->nullable()->constrained('zones');
            $table->unsignedBigInteger('min_order_amount')->nullable();
            $table->unsignedBigInteger('max_order_amount')->nullable();
            $table->unsignedBigInteger('discount_value')->nullable();
            $table->unsignedBigInteger('fixed_delivery_price')->nullable();
            $table->integer('currency_factor')->default(100);
            $table->boolean('first_order_only')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index(['type', 'subtype', 'is_active']);

        });
        Schema::create('promotion_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unique(['promotion_id', 'locale']);
        });
        Schema::create('promotion_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->string('target_type')->default(PromotionTargetTypeEnum::STORE);
            $table->bigInteger('target_id');
            $table->boolean('is_excluded')->default(false);
            
            $table->unique(['promotion_id', 'target_type', 'target_id']);
        });
        Schema::create('promotion_fixed_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->unsignedBigInteger('fixed_price')->nullable();
            
            $table->index(['promotion_id', 'store_id']);
            $table->index(['promotion_id', 'product_id']);
        });

        Schema::create('user_promotion_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('usage_count')->default(1);
            $table->timestamp('last_used_at')->default(now());
            
            $table->unique(['promotion_id', 'user_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('promotion_translations');
        Schema::dropIfExists('promotion_targets');
        Schema::dropIfExists('promotion_fixed_prices');
        Schema::dropIfExists('user_promotion_usage');
    }
};
