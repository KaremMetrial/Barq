<?php

use App\Enums\ProductStatusEnum;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('max_cart_quantity')->default(2);
            $table->string('status')->default(ProductStatusEnum::PENDING->value);
            $table->text('note')->nullable();
            $table->boolean('is_reviewed')->default(true);
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
