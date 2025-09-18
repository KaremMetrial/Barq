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
        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->index();
            $table->string('name');

            $table->timestamps();

            $table->foreignId('city_id')->constrained()->cascadeOnDelete();

            $table->unique(['locale', 'city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_translations');
    }
};
