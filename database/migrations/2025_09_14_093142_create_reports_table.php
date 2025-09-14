<?php

use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(ReportTypeEnum::OTHER->value);
            $table->dateTime('resolved_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->string('status')->default(ReportStatusEnum::PENDING->value);
            $table->text('description');
            $table->morphs('reportable');
            $table->timestamps();

            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            // $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
