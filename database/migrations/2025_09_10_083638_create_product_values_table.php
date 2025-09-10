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
        Schema::create('product_values', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_modifier', 10, 3)->default(0);
            $table->timestamps();

            $table->foreignId('option_id')->constrained('product_options')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_values');
    }
};
