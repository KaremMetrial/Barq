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
        Schema::table('orders', function (Blueprint $table) {
            // Drop the existing string column
            $table->dropColumn('delivery_address');

            // Add new foreign key column
            $table->foreignId('delivery_address_id')->nullable()->constrained('addresses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key column
            $table->dropForeign(['delivery_address_id']);
            $table->dropColumn('delivery_address_id');

            // Restore the original string column
            $table->string('delivery_address')->nullable();
        });
    }
};
