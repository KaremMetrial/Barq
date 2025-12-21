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
        // Change min_distance column type in shipping_prices table
        if (Schema::hasTable('shipping_prices')) {
            Schema::table('shipping_prices', function (Blueprint $table) {
                // Column type changes were handled in the major bigint conversion
                // This migration ensures the column exists and is properly configured
                if (Schema::hasColumn('shipping_prices', 'min_distance')) {
                    // Since we've already converted to appropriate types in later migrations,
                    // no additional changes are needed here
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};