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
        Schema::create('product_nutrition', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('calories')->nullable();
            $table->decimal('fat', 8, 2)->nullable();
            $table->decimal('protein', 8, 2)->nullable();
            $table->decimal('carbohydrates', 8, 2)->nullable();
            $table->decimal('sugar', 8, 2)->nullable();
            $table->decimal('fiber', 8, 2)->nullable();
            $table->timestamps();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_nutrition');
    }
};
