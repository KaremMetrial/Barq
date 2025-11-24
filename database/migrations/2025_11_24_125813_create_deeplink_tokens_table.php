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
        Schema::create('deeplink_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->enum('type', ['product','store']);
            $table->unsignedBigInteger('target_id');
            $table->string('referrer_code')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->default(true);
            $table->ipAddress('click_ip')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deeplink_tokens');
    }
};
