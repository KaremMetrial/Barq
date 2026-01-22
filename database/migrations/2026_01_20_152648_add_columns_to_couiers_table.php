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
        Schema::table('couiers', function (Blueprint $table) {
            $table->boolean('auto_accept_orders')->default(false);
            $table->boolean('accept_overtime')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couiers', function (Blueprint $table) {
            $table->dropColumn('auto_accept_orders');
            $table->dropColumn('accept_overtime');
        });
    }
};
