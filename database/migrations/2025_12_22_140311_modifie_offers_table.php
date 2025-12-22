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
        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_amount')->nullable()->change();
            if(Schema::hasColumn('offers', 'discount_amount_minor')) {
                $table->dropColumn('discount_amount_minor');
            }
            if(!Schema::hasColumn('offers', 'currency_factor')) {
                $table->unsignedInteger('currency_factor')->nullable()->after('discount_amount');
            }
            if(!Schema::hasColumn('offers', 'currency_code')) {
                $table->string('currency_code', 3)->nullable()->after('currency_factor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 3)->nullable()->change();
            if(!Schema::hasColumn('offers', 'discount_amount_minor')) {
                $table->unsignedBigInteger('discount_amount_minor')->nullable()->after('discount_amount');
            }
            if(Schema::hasColumn('offers', 'currency_factor')) {
                $table->dropColumn('currency_factor');
            }
            if(Schema::hasColumn('offers', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
