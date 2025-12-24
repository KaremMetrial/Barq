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
        Schema::table('balances', function (Blueprint $table) {
            $table->unsignedBigInteger('available_balance')->change();
            $table->unsignedBigInteger('pending_balance')->change();
            $table->unsignedBigInteger('total_balance')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balances', function (Blueprint $table) {
            $table->decimal('available_balance', 10, 2)->change();
            $table->decimal('pending_balance', 10, 2)->change();
            $table->decimal('total_balance', 10, 2)->change();
        });
    }
};
