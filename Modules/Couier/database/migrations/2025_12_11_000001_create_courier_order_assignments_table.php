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
        Schema::create('courier_order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('couiers')->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('courier_shift_id')->nullable()->constrained('couier_shifts')->nullOnDelete();

            $table->enum('status', [
                'assigned',
                'accepted',
                'in_transit',
                'delivered',
                'failed',
                'rejected',
                'timed_out',
                'cancelled'
            ])->default('assigned');

            // Timing
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto timeout
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Geographic data
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lng', 11, 8)->nullable();
            $table->decimal('current_courier_lat', 10, 8)->nullable();
            $table->decimal('current_courier_lng', 11, 8)->nullable();

            // Calculations
            $table->decimal('estimated_distance_km', 6, 2)->nullable();
            $table->decimal('actual_distance_km', 6, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->decimal('estimated_earning', 8, 2)->nullable();
            $table->decimal('actual_earning', 8, 2)->nullable();

            // Priority
            $table->enum('priority_level', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Review & feedback
            $table->integer('courier_rating')->nullable(); // 1-5 stars
            $table->text('courier_feedback')->nullable();
            $table->integer('customer_rating')->nullable();
            $table->text('customer_feedback')->nullable();

            // Rejection reason (if applicable)
            $table->string('rejection_reason')->nullable();

            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('assignment_metadata')->nullable(); // Store additional data

            $table->timestamps();

            // Constraints
            $table->unique(['order_id']); // Each order can only be assigned once
            $table->index(['courier_id', 'status']);
            $table->index(['status', 'assigned_at']);
            $table->index(['expires_at']); // For timeout cleanup
            $table->index(['priority_level', 'status']); // For priority ordering

            // Performance indexes for geographic queries
            $table->index(['current_courier_lat', 'current_courier_lng'], 'geo_location_idx');
            // Note: Spatial index removed due to MySQL index name length limits
            // Can be added later with custom index naming if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_order_assignments');
    }
};
