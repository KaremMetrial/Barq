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
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreignId('product_value_id')->constrained('product_values')->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
