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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->enum('target_type', ['all_users', 'specific_users', 'top_users', 'loyalty_tiers']);
            $table->json('target_data')->nullable(); // For specific user IDs or criteria
            $table->integer('top_users_count')->nullable(); // For top N users
            $table->enum('performance_metric', ['order_count', 'total_spent', 'loyalty_points', 'avg_rating'])->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_sent')->default(0);
            $table->integer('total_failed')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('admin_id')->nullable()->constrained('admins');
            $table->boolean('is_scheduled')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['target_type', 'is_sent']);
            $table->index('scheduled_at');
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
