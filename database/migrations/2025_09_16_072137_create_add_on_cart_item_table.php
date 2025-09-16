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
        Schema::create('add_on_cart_item', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_modifier', 10, 3);
            $table->integer('quantity');
            $table->timestamps();

            $table->foreignid('cart_item_id')->constrained()->cascadeOnDelete();
            $table->foreignid('add_on_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_on_cart_item');
    }
};
