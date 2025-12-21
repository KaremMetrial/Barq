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

        if (!Schema::hasColumn('add_on_order', 'price_bigint')) {
            Schema::table('add_on_order', function (Blueprint $table) {
                $table->unsignedBigInteger('price_bigint')->nullable()->after('price');
            });

            // Backfill values: convert decimal to minor units (multiply by 100 and round)
            DB::statement('UPDATE add_on_order SET price_bigint = CAST(ROUND(price * 100) AS UNSIGNED)');
        }

        if (Schema::hasColumn('add_on_order', 'price')) {
            Schema::table('add_on_order', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
        if (Schema::hasColumn('add_on_order', 'price_bigint') && !Schema::hasColumn('add_on_order', 'price')) {
            Schema::table('add_on_order', function (Blueprint $table) {
                $table->renameColumn('price_modifier_bigint', 'price');
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the price column exists (it should be the bigint version at this point)
        if (Schema::hasColumn('add_on_order', 'price')) {
            // Add back decimal column without after() to avoid issues if 'price' gets renamed
            if (!Schema::hasColumn('add_on_order', 'price_decimal')) {
                Schema::table('add_on_order', function (Blueprint $table) {
                    $table->decimal('price_decimal', 10, 3)->nullable();
                });

                // Migrate data from bigint to decimal by dividing by 100
                DB::statement('UPDATE add_on_order SET price_decimal = CAST(price AS DECIMAL(10,3)) / 100');
            }

            // Drop bigint column
            Schema::table('add_on_order', function (Blueprint $table) {
                $table->dropColumn('price');
            });

            // Rename decimal column to original name
            if (Schema::hasColumn('add_on_order', 'price_decimal') && !Schema::hasColumn('add_on_order', 'price')) {
                Schema::table('add_on_order', function (Blueprint $table) {
                    $table->renameColumn('price_decimal', 'price');
                });
            }
        }
    }
};
