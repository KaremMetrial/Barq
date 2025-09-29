<?php

use App\Enums\AdStatusEnum;
use App\Enums\AdTypeEnum;
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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('ad_number')->unique();
            $table->string('type')->default(AdTypeEnum::STANDARD->value);
            $table->boolean('is_active')->default(true);
            $table->string('status')->default(AdStatusEnum::PENDING->value);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('media_path')->nullable();
            $table->morphs('adable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
