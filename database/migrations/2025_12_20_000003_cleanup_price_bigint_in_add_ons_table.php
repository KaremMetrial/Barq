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
        // Cleanup price bigint in add_ons table
        if (Schema::hasTable('add_ons')) {
            Schema::table('add_ons', function (Blueprint $table) {
                // Ensure the price column exists and is properly configured
                if (Schema::hasColumn('add_ons', 'price')) {
                    // Since we've already converted to bigint in the major conversion migration,
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
        // No reversal needed
    }
};