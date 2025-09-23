<?php

use App\Enums\ProductWatermarkPositionEnum;
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
        Schema::create('product_watermarks', function (Blueprint $table) {
            $table->id();
            $table->string('image_url');
            $table->string('position')->default(ProductWatermarkPositionEnum::BOTTOM_LEFT->value);
            $table->integer('opacity');
            $table->timestamps();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_watermarks');
    }
};
