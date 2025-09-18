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
        Schema::create('governorate_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->index();
            $table->string('name');
            $table->timestamps();

            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();

            $table->unique(['governorate_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governorate_translations');
    }
};
