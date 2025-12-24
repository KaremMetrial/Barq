<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE product_prices
            SET sale_price = 0
            WHERE sale_price IS NULL OR sale_price < 0
        ");

        DB::statement("
            UPDATE product_prices
            SET sale_price = FLOOR(sale_price)
            WHERE sale_price != FLOOR(sale_price)
        ");

        Schema::table('product_prices', function (Blueprint $table) {

            if (Schema::hasColumn('product_prices', 'price_minor')) {
                $table->dropColumn('price_minor');
            }

            if (Schema::hasColumn('product_prices', 'sale_price')) {
                $table->unsignedBigInteger('sale_price')->default(0)->change();
            }

            if (Schema::hasColumn('product_prices', 'sale_price_minor')) {
                $table->dropColumn('sale_price_minor');
            }

            if (Schema::hasColumn('product_prices', 'purchase_price_minor')) {
                $table->dropColumn('purchase_price_minor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {

            if (!Schema::hasColumn('product_prices', 'price_minor')) {
                $table->decimal('price_minor', 10, 2)->nullable();
            }

            if (Schema::hasColumn('product_prices', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->change();
            }

            if (!Schema::hasColumn('product_prices', 'sale_price_minor')) {
                $table->decimal('sale_price_minor', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('product_prices', 'purchase_price_minor')) {
                $table->decimal('purchase_price_minor', 10, 2)->nullable();
            }
        });
    }
};
