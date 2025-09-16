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
        Schema::create('order_proofs', function (Blueprint $table) {
            $table->id();
            $table->string('image_url');
            $table->timestamps();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->morphs('order_proofable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_proofs');
    }
};
