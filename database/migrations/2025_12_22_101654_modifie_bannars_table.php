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
        Schema::table('banners', function (Blueprint $table) {
            $table->dropMorphs('bannerable');
            $table->string('bannerable_type')->nullable();
            $table->unsignedBigInteger('bannerable_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bannars', function (Blueprint $table) {
            $table->morphs('bannerable');
            $table->dropColumn('bannerable_type');
            $table->dropColumn('bannerable_id');
        });
    }
};
