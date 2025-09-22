<?php

use App\Enums\CompaignParicipationStatusEnum;
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
        Schema::create('compaign_paricipations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(CompaignParicipationStatusEnum::PENDING);
            $table->text('notes')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->foreignId('compaign_id')->constrained('compaigns')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->unique(['compaign_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compaign_paricipations');
    }
};
