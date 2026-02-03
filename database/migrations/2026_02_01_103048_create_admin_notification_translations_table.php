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
        Schema::create('admin_notification_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_notification_id');
            $table->string('locale', 10);
            $table->string('title');
            $table->text('body');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('admin_notification_id')
                ->references('id')
                ->on('admin_notifications')
                ->onDelete('cascade');
            
            // Unique constraint for locale per notification
            $table->unique(['admin_notification_id', 'locale'], 'admin_notification_translations_unique');
            
            // Index for locale queries
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notification_translations');
    }
};
