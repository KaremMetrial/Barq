<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix precision for sale_price in product_prices table
        if (Schema::hasTable('product_prices')) {
            Schema::table('product_prices', function (Blueprint $table) {
                // Ensure the column exists and modify precision if needed
                if (Schema::hasColumn('product_prices', 'sale_price')) {
                    // Since we've already converted to bigint in a later migration,
                    // we'll ensure the column is properly configured
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as this was a precision fix
    }
};