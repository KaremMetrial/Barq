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
        Schema::table('carts', function (Blueprint $table) {
            // إضافة فهارس للأداء
            $table->index('cart_key', 'idx_carts_cart_key');
            $table->index(['store_id', 'user_id'], 'idx_carts_store_user');
            $table->index('created_at', 'idx_carts_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_cart_key');
            $table->dropIndex('idx_carts_store_user');
            $table->dropIndex('idx_carts_created_at');
        });
    }
};
