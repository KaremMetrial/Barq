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
        Schema::table('product_availabilities', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->after('product_id');
            $table->unique(['product_id', 'store_id'], 'product_store_availability_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_availabilities', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            $table->dropUnique('product_store_availability_unique');
        });
    }
};
