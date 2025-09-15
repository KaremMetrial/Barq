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
        Schema::create('pharmacy_info_translations', function (Blueprint $table) {
            $table->id();
            $table->string('generic_name');
            $table->string('common_use');
            $table->string('locale')->index();
            $table->timestamps();

            $table->foreignId('pharmacy_info_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_info_translations');
    }
};
