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
        Schema::table('compaigns', function (Blueprint $table) {
            $table->foreignId('reward_id')->nullable()->after('is_active')->constrained('rewards')->nullOnDelete();
        });

        Schema::table('compaign_paricipations', function (Blueprint $table) {
            $table->decimal('points', 10, 2)->default(0)->after('responded_at');
            // Adding an index on points for faster leaderboard queries
            $table->index('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compaigns', function (Blueprint $table) {
            $table->dropForeign(['reward_id']);
            $table->dropColumn('reward_id');
        });

        Schema::table('compaign_paricipations', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
