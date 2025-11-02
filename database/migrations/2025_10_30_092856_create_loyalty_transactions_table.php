<?php

use App\Enums\LoyaltyTrransactionTypeEnum;
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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->default(LoyaltyTrransactionTypeEnum::EARNED); // earned, redeemed, expired, adjusted
            $table->decimal('points', 10, 2);
            $table->decimal('points_balance_after', 10, 2); // Balance after transaction
            $table->string('description')->nullable();
            $table->morphs('referenceable'); // Order, Admin adjustment, etc.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
