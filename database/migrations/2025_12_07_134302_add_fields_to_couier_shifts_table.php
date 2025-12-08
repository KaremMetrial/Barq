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
        Schema::table('couier_shifts', function (Blueprint $table) {
            $table->foreignId('shift_template_id')->nullable()->after('couier_id')->constrained('shift_templates')->nullOnDelete();
            $table->dateTime('expected_end_time')->nullable()->after('end_time');
            $table->dateTime('break_start')->nullable()->after('expected_end_time');
            $table->dateTime('break_end')->nullable()->after('break_start');
            $table->integer('overtime_minutes')->default(0)->after('break_end');
            $table->decimal('overtime_pay', 10, 2)->default(0)->after('overtime_minutes');
            $table->integer('total_orders')->default(0)->after('overtime_pay');
            $table->decimal('total_earnings', 10, 2)->default(0)->after('total_orders');
            $table->text('notes')->nullable()->after('total_earnings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couier_shifts', function (Blueprint $table) {
            $table->dropForeign(['shift_template_id']);
            $table->dropColumn([
                'shift_template_id',
                'expected_end_time',
                'break_start',
                'break_end',
                'overtime_minutes',
                'overtime_pay',
                'total_orders',
                'total_earnings',
                'notes'
            ]);
        });
    }
};
