<?php

use App\Enums\OfferStatusEnum;
use App\Enums\SaleTypeEnum;
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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('discount_type')->default(SaleTypeEnum::PERCENTAGE->value);
            $table->decimal('discount_amount', 8, 3);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_flash_sale')->default(false);
            $table->boolean('has_stock_limit')->default(false);
            $table->unsignedInteger('stock_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default(OfferStatusEnum::PENDING->value);
            $table->morphs('offerable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
