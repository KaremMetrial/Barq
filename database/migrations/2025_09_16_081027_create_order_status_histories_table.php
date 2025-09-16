<?php

use Carbon\Carbon;
use App\Enums\OrderStatusHistoryEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(OrderStatusHistoryEnum::PENDING->value);
            $table->dateTime('changed_at')->default(Carbon::now());
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
