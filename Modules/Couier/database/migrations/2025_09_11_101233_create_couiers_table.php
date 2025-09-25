<?php

use App\Enums\CouierAvaliableStatusEnum;
use App\Enums\UserStatusEnum;
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
        Schema::create('couiers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('license_number')->unique();
            $table->string('avaliable_status')->default(CouierAvaliableStatusEnum::OFF->value);
            $table->double('avg_rate')->default(0);
            $table->string('status')->default(UserStatusEnum::PENDING->value);
            $table->softDeletes();
            $table->timestamps();

            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couiers');
    }
};
