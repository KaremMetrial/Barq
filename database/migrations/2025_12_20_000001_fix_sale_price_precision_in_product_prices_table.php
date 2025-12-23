<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Adds precision specification to sale_price column
     * Issue: sale_price was defined as decimal() without precision
     * which defaults to decimal(8,1) instead of decimal(8,3)
     * in product_prices table that was missing in migration 2025_11_11_124609
     * This causes data loss and inconsistency with other price columns.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_price', 8, 3)->nullable()->change();
            // Change sale_price to have correct precision (8,3) to match price column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->decimal('sale_price')->nullable()->change();
            // Revert to decimal without precision (though this is the broken state)
        });
    }
};
