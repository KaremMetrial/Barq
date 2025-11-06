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
            // إضافة قيد على الكمية لضمان أنها موجبة
            $table->unsignedInteger('quantity')->default(1)->change();

            // إضافة قيد على السعر الإجمالي
            $table->decimal('total_price', 10, 3)->default(0)->change();
        });

        // إضافة قيد التحقق من الكمية (إذا كانت قاعدة البيانات تدعمها)
        // DB::statement('ALTER TABLE cart_items ADD CONSTRAINT chk_cart_items_quantity CHECK (quantity > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->decimal('total_price', 10, 3)->change();
        });

        // DB::statement('ALTER TABLE cart_items DROP CONSTRAINT chk_cart_items_quantity');
    }
};
