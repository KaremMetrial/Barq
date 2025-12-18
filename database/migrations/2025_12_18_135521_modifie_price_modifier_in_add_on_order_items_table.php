<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasColumn('add_on_order_item', 'price_modifier')){
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->unsignedBigInteger('price_modifier_bigint')->nullable()->after('price_modifier');
            });

            DB::statement('UPDATE add_on_order_item SET price_modifier_bigint = CAST(ROUND(price_modifier * 100) AS UNSIGNED)');
        }
        if(Schema::hasColumn('add_on_order_item', 'price_modifier')){
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_bigint') && !Schema::hasColumn('add_on_order_items', 'price_modifier')) {
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
        if(!Schema::hasColumn('add_on_order_item', 'price_modifier_decimal')){
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->decimal('price_modifier_decimal', 10, 3)->nullable()->after('price_modifier');
            });

            DB::statement('UPDATE add_on_order_item SET price_modifier_decimal = CAST(price_modifier AS DECIMAL(10,3)) / 100');
        }
        if(Schema::hasColumn('add_on_order_item', 'price_modifier')){
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->dropColumn('price_modifier');
            });
        }
        if (Schema::hasColumn('add_on_order_item', 'price_modifier_decimal')) {
            Schema::table('add_on_order_item', function (Blueprint $table) {
                $table->renameColumn('price_modifier_decimal', 'price_modifier');
            });
        }
    }
};
