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
        // Schema::create('courier_order_assignments', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('courier_id')->constrained('couiers')->onDelete('cascade');
        //     $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
        //     $table->foreignId('courier_shift_id')->nullable()->constrained('couier_shifts')->onDelete('set null');
        //     $table->string('status')->default('assigned'); // assigned, accepted, in_transit, delivered, failed, rejected, timed_out
        //     $table->timestamp('assigned_at')->nullable();
        //     $table->timestamp('accepted_at')->nullable();
        //     $table->timestamp('expires_at')->nullable();
        //     $table->timestamp('started_at')->nullable();
        //     $table->timestamp('completed_at')->nullable();
        //     $table->decimal('pickup_lat', 10, 7)->nullable();
        //     $table->decimal('pickup_lng', 10, 7)->nullable();
        //     $table->decimal('delivery_lat', 10, 7)->nullable();
        //     $table->decimal('delivery_lng', 10, 7)->nullable();
        //     $table->decimal('current_courier_lat', 10, 7)->nullable();
        //     $table->decimal('current_courier_lng', 10, 7)->nullable();
        //     $table->decimal('estimated_distance_km', 2, 2)->nullable();
        //     $table->decimal('actual_distance_km', 2, 2)->nullable();
        //     $table->decimal('estimated_duration_minutes', 5, 2)->nullable();
        //     $table->decimal('actual_duration_minutes', 5, 2)->nullable();
        //     $table->decimal('estimated_earning', 8, 2)->nullable();
        //     $table->decimal('actual_earning', 8, 2)->nullable();
        //     $table->string('priority_level')->default('normal');
        //     $table->integer('courier_rating')->nullable();
        //     $table->text('courier_feedback')->nullable();
        //     $table->integer('customer_rating')->nullable();
        //     $table->text('customer_feedback')->nullable();
        //     $table->text('rejection_reason')->nullable();
        //     $table->text('notes')->nullable();
        //     $table->json('assignment_metadata')->nullable();
        //     $table->timestamps();

        //     // Indexes
        //     $table->index(['courier_id', 'status']);
        //     $table->index(['order_id']);
        //     $table->index(['status', 'expires_at']);
        //     $table->index(['courier_shift_id']);
        //     $table->index(['created_at']);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    //     Schema::dropIfExists('courier_order_assignments');
    }
};
