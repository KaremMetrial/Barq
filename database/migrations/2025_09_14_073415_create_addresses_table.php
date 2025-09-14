<?php

use App\Enums\AddressTypeEnum;
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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('type')->default(AddressTypeEnum::HOME->value);
            $table->morphs('addressable');
            $table->softDeletes();
            $table->timestamps();

            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
