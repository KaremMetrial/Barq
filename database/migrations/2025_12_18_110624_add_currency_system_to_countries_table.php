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
        Schema::table('countries', function (Blueprint $table) {
            $table->string('currency_unit')
                ->nullable()
                ->after('currency_name');

            $table->unsignedBigInteger('currency_factor')
                ->default(100)
                ->after('currency_unit');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn([
                'currency_unit',
                'currency_factor',
            ]);
        });
    }
};
