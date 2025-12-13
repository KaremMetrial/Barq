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
        Schema::create('order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('courier_order_assignments')->onDelete('cascade');
            $table->enum('type', ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature']);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->json('metadata')->nullable(); // GPS coordinates, timestamp, device info
            $table->timestamps();

            $table->index(['assignment_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_receipts');
    }
};
