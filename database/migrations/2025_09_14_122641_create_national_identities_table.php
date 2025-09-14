<?php

use App\Enums\NationalIdentityTypeEnum;
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
        Schema::create('national_identities', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(NationalIdentityTypeEnum::NATTIONAL_ID->value);
            $table->string('national_id')->unique();
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            $table->morphs('identityable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_identities');
    }
};
