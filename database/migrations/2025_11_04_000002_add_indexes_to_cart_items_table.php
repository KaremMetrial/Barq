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
        Schema::table('cart_items', function (Blueprint $table) {
            // إضافة فهارس للأداء
            $table->index(['cart_id', 'product_id'], 'idx_cart_items_cart_product');
            $table->index('product_id', 'idx_cart_items_product');
            $table->index('added_by_user_id', 'idx_cart_items_added_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart_product');
            $table->dropIndex('idx_cart_items_product');
            $table->dropIndex('idx_cart_items_added_by');
        });
    }
};
