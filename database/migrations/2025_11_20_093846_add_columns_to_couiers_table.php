<?php

use App\Enums\PlanTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('couiers', function (Blueprint $table) {
            $table->string('commission_type')->default(PlanTypeEnum::SUBSCRIPTION);
            $table->decimal('commission_amount')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couiers', function (Blueprint $table) {
            $table->dropColumn('commission_type');
            $table->dropColumn('commission_amount');
        });
    }
};
