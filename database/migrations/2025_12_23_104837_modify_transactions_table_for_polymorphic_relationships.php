<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, make user_id nullable to maintain backward compatibility
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('amount')->nullable()->change();
        });

        // Add polymorphic relationship columns
        Schema::table('transactions', function (Blueprint $table) {
            // Add transactionable columns for polymorphic relationship
            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();

            // Create index for polymorphic relationship
            $table->index(['transactionable_type', 'transactionable_id'], 'transactions_transactionable_index');

            // Add indexes for better performance
            $table->index('transactionable_id');
            $table->index('transactionable_type');
        });

        // Update existing records to maintain backward compatibility
        DB::table('transactions')
            ->whereNull('transactionable_type')
            ->whereNotNull('user_id')
            ->update([
                'transactionable_type' => 'user',
                'transactionable_id' => DB::raw('user_id')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Remove polymorphic columns
            $table->dropColumn(['transactionable_type', 'transactionable_id']);

            // Remove indexes
            $table->dropIndex('transactions_transactionable_index');
            $table->dropIndex(['transactionable_id']);
            $table->dropIndex(['transactionable_type']);

            // Make user_id non-nullable again to maintain original constraint
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
