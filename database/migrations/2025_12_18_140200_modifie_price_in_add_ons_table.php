<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasColumn('add_ons', 'price')) {
            Schema::table('add_ons', function (Blueprint $table) {
                $table->unsignedBigInteger('price_bigint')->nullable()->after('price');
            });

            DB::statement('UPDATE add_ons SET price_bigint = CAST(ROUND(price * 100) AS UNSIGNED)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            //
        });
    }
};
