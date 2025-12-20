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
        // Ensure table exists
        if (! Schema::hasTable('add_on_order_item')) {
            return;
        }

        // Add bigint column if it doesn't exist
        if (! Schema::hasColumn('add_on_order_item', 'price_modifier_bigint')) {
            $hasAnchor = Schema::hasColumn('add_on_order_item', 'price_modifier');

            Schema::table('add_on_order_item', function (Blueprint $table) use ($hasAnchor) {
                if ($hasAnchor) {
                    // anchor exists so we can safely use after()
                    $table->unsignedBigInteger('price_modifier_bigint')->nullable()->after('price_modifier');
                } else {
                    // anchor missing; add without after() to avoid SQL errors
                    $table->unsignedBigInteger('price_modifier_bigint')->nullable();
                }
            });

            // Backfill: convert decimal to minor units (multiply by 100 and round)
            if ($hasAnchor) {
                try {
                    DB::statement('UPDATE add_on_order_item SET price_modifier_bigint = CAST(ROUND(price_modifier * 100) AS UNSIGNED)');
                } catch (\Exception $e) {
                    // If the update fails for some reason, do not break the migration
                }
            }
        }

        // Drop old decimal column if present
        if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }

        // Rename new column to original name if needed
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_bigint') && ! Schema::hasColumn('add_on_order_item', 'price_modifier')) {
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
        if (! Schema::hasTable('add_on_order_item')) {
            return;
        }

        // Add back decimal column (use anchor check for after())
        if (! Schema::hasColumn('add_on_order_item', 'price_modifier_decimal')) {
            $hasAnchor = Schema::hasColumn('add_on_order_item', 'price_modifier');

            Schema::table('add_on_order_item', function (Blueprint $table) use ($hasAnchor) {
                if ($hasAnchor) {
                    $table->decimal('price_modifier_decimal', 10, 3)->nullable()->after('price_modifier');
                } else {
                    $table->decimal('price_modifier_decimal', 10, 3)->nullable();
                }
            });

            // Migrate data from bigint to decimal by dividing by 100
            if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
                try {
                    DB::statement('UPDATE add_on_order_item SET price_modifier_decimal = CAST(price_modifier AS DECIMAL(10,3)) / 100');
                } catch (\Exception $e) {
                    // ignore failures to keep rollback safe
                }
            }
        }

        // Drop bigint column
        if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }

        // Rename back to original decimal name
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_decimal') && ! Schema::hasColumn('add_on_order_item', 'price_modifier')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->renameColumn('price_modifier_decimal', 'price_modifier');
            });
        }
    }
};
