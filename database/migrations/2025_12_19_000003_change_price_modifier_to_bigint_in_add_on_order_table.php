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
        // Add bigint column if it doesn't exist
        if (!Schema::hasColumn('add_on_order_item', 'price_modifier_bigint')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->unsignedBigInteger('price_modifier_bigint')->nullable()->after('price_modifier');
            });

            // Backfill: convert decimal to minor units (multiply by 100 and round)
            DB::statement('UPDATE add_on_order_item SET price_modifier_bigint = CAST(ROUND(price_modifier * 100) AS UNSIGNED)');
        }

        // Drop old decimal column if present
        if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }

        // Rename new column to original name if needed
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_bigint') && !Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->renameColumn('price_modifier_bigint', 'price_modifier');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back decimal column
        if (!Schema::hasColumn('add_on_order_item', 'price_modifier_decimal')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->decimal('price_modifier_decimal', 10, 3)->nullable()->after('price_modifier');
            });

            // Migrate data from bigint to decimal by dividing by 100
            DB::statement('UPDATE add_on_order_item SET price_modifier_decimal = CAST(price_modifier AS DECIMAL(10,3)) / 100');
        }

        // Drop bigint column
        if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }

        // Rename back to original decimal name
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_decimal') && !Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->renameColumn('price_modifier_decimal', 'price_modifier');
            });
        }
    }
};
