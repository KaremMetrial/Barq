<?php

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
        Schema::create('product_price_sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('sale_price', 8, 3);
            $table->string('sale_type')->default(SaleTypeEnum::FIXED->value); // e.g., 'percentage' or 'fixed'
            $table->timestamps();

            $table->foreignId('product_price_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_sales');
    }
};
