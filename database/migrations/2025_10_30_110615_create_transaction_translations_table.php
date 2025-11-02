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
        Schema::create('transaction_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->unique(['transaction_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_translations');
    }
};
