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
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_hash')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->dateTime('otp_verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp_hash');
            $table->dropColumn('otp_expires_at');
            $table->dropColumn('otp_verified_at');
        });
    }
};
