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
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_amount')->default(0)->change();
            $table->unsignedBigInteger('minimum_order_amount')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('discount_amount', 8, 3)->default(0.0)->change();
            $table->decimal('minimum_order_amount', 8, 3)->default(1.0)->change();
        });
    }
};
