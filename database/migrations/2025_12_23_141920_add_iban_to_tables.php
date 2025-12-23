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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('iban')->nullable();
        });
        Schema::table('couiers', function (Blueprint $table) {
            $table->string('iban')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('iban');
        });
        Schema::table('couiers', function (Blueprint $table) {
            $table->dropColumn('iban');
        });
    }
};
