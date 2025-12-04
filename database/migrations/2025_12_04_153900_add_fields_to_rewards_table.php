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
        Schema::table('rewards', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->unsignedInteger('max_redemptions_per_user')->nullable()->after('usage_count');
            $table->unsignedInteger('total_redemptions')->default(0)->after('max_redemptions_per_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn(['image', 'max_redemptions_per_user', 'total_redemptions']);
        });
    }
};
