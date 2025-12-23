<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the type column from enum to string and add payment_method_id
        DB::statement('ALTER TABLE transactions MODIFY type VARCHAR(255)');
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->constrained()->cascadeOnDelete()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
        // Revert type column back to enum
        DB::statement("ALTER TABLE transactions MODIFY type ENUM('increment', 'decrement')");
    }
};
