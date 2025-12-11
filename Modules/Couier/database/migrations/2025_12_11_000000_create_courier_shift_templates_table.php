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
        Schema::create('courier_shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('couiers')->onDelete('cascade');
            $table->foreignId('shift_template_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['courier_id', 'shift_template_id']);

            // Indexes for performance
            $table->index(['courier_id', 'is_active']);
            $table->index('shift_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_shift_templates');
    }
};
