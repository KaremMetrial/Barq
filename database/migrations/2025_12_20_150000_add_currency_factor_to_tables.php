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
        // Add currency_factor to coupons table
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                if (!Schema::hasColumn('coupons', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to courier_order_assignments table
        if (Schema::hasTable('courier_order_assignments')) {
            Schema::table('courier_order_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('courier_order_assignments', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to offers table
        if (Schema::hasTable('offers')) {
            Schema::table('offers', function (Blueprint $table) {
                if (!Schema::hasColumn('offers', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to plans table
        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                if (!Schema::hasColumn('plans', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to pos_shifts table
        if (Schema::hasTable('pos_shifts')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_shifts', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to product_option_values table
        if (Schema::hasTable('product_option_values')) {
            Schema::table('product_option_values', function (Blueprint $table) {
                if (!Schema::hasColumn('product_option_values', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to product_price_sales table
        if (Schema::hasTable('product_price_sales')) {
            Schema::table('product_price_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('product_price_sales', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to stores table
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (!Schema::hasColumn('stores', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to transactions table
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }

        // Add currency_factor to balances table
        if (Schema::hasTable('balances')) {
            Schema::table('balances', function (Blueprint $table) {
                if (!Schema::hasColumn('balances', 'currency_factor')) {
                    $table->unsignedBigInteger('currency_factor')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove currency_factor from coupons table
        if (Schema::hasTable('coupons') && Schema::hasColumn('coupons', 'currency_factor')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from courier_order_assignments table
        if (Schema::hasTable('courier_order_assignments') && Schema::hasColumn('courier_order_assignments', 'currency_factor')) {
            Schema::table('courier_order_assignments', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from offers table
        if (Schema::hasTable('offers') && Schema::hasColumn('offers', 'currency_factor')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from plans table
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'currency_factor')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from pos_shifts table
        if (Schema::hasTable('pos_shifts') && Schema::hasColumn('pos_shifts', 'currency_factor')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from product_option_values table
        if (Schema::hasTable('product_option_values') && Schema::hasColumn('product_option_values', 'currency_factor')) {
            Schema::table('product_option_values', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from product_price_sales table
        if (Schema::hasTable('product_price_sales') && Schema::hasColumn('product_price_sales', 'currency_factor')) {
            Schema::table('product_price_sales', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from stores table
        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'currency_factor')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from transactions table
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'currency_factor')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }

        // Remove currency_factor from balances table
        if (Schema::hasTable('balances') && Schema::hasColumn('balances', 'currency_factor')) {
            Schema::table('balances', function (Blueprint $table) {
                $table->dropColumn('currency_factor');
            });
        }
    }
};
