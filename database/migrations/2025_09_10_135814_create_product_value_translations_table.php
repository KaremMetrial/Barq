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
        Schema::create('product_value_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('locale')->index();
            $table->timestamps();

            $table->foreignId('product_value_id')->constrained('product_values')->cascadeOnDelete();
            $table->unique(['product_value_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_value_translations');
    }
};
