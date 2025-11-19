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
        Schema::create('couier_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('couier_id')->constrained('couiers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couier_zone');
    }
};
