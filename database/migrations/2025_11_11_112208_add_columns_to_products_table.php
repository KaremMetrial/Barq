<?php

use App\Enums\DeliveryTypeUnitEnum;
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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('preparation_time')->nullable()->after('weight');
            $table->string('preparation_time_unit')->default(DeliveryTypeUnitEnum::MINUTE)->after('preparation_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('preparation_time');
            $table->dropColumn('preparation_time_unit');
        });
    }
};
