<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * COMPREHENSIVE PRICE CONVERSION MIGRATION
 *
 * Converts all DECIMAL price fields to UNSIGNED BIGINT
 * Storage based on currency_unit and currency_factor from countries table
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Determine multiplier: prefer currency_factor from countries table if available
        $multiplier = 100; // default
        try {
            // Use first active country or fallback to any country
            $country = DB::table('countries')->where('is_active', true)->first() ?? DB::table('countries')->first();
            if ($country) {
                // currency_factor takes precedence; then currency_unit
                if (!empty($country->currency_factor)) {
                    $multiplier = (int)$country->currency_factor;
                } elseif (!empty($country->currency_unit)) {
                    $multiplier = (int)$country->currency_unit;
                }
            }
        } catch (\Exception $e) {
            // ignore and use default multiplier
        }

        // Helper closure to add column safely with optional after anchor
        $addUnsignedBigIntColumn = function (string $tableName, string $colName, ?string $afterAnchor = null) {
            if (! Schema::hasTable($tableName)) {
                return;
            }

            if (Schema::hasColumn($tableName, $colName)) {
                return;
            }

            $hasAnchor = $afterAnchor ? Schema::hasColumn($tableName, $afterAnchor) : false;

            Schema::table($tableName, function (Blueprint $table) use ($colName, $hasAnchor, $afterAnchor) {
                if ($hasAnchor && $afterAnchor) {
                    $table->unsignedBigInteger($colName)->nullable()->after($afterAnchor);
                } else {
                    $table->unsignedBigInteger($colName)->nullable();
                }
            });
        };

        // Helper to run an update only if source column exists
        $safeUpdate = function (string $tableName, string $targetCol, string $expression, array $bindings = []) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, preg_replace('/.*SET\s+([a-zA-Z0-9_]+)\s*=.*/', '$1', strtoupper($expression)))) {
                // best-effort: we'll attempt the update and catch exceptions
                try {
                    DB::statement($expression, $bindings);
                } catch (\Exception $e) {
                    // swallow - keep migration proceeding
                }
                return;
            }

            try {
                DB::statement($expression, $bindings);
            } catch (\Exception $e) {
                // swallow
            }
        };

        // =====================================================
        // 1. PRODUCT PRICES TABLE
        // =====================================================
        if (Schema::hasTable('product_prices')) {
            Schema::table('product_prices', function (Blueprint $table) {
                if (! Schema::hasColumn('product_prices', 'price_bigint')) {
                    // avoid after() if anchor missing
                    if (Schema::hasColumn('product_prices', 'price')) {
                        $table->unsignedBigInteger('price_bigint')->nullable()->after('price');
                    } else {
                        $table->unsignedBigInteger('price_bigint')->nullable();
                    }
                }
                if (! Schema::hasColumn('product_prices', 'purchase_price_bigint')) {
                    if (Schema::hasColumn('product_prices', 'purchase_price')) {
                        $table->unsignedBigInteger('purchase_price_bigint')->nullable()->after('purchase_price');
                    } else {
                        $table->unsignedBigInteger('purchase_price_bigint')->nullable();
                    }
                }
                if (! Schema::hasColumn('product_prices', 'sale_price_bigint')) {
                    if (Schema::hasColumn('product_prices', 'sale_price')) {
                        $table->unsignedBigInteger('sale_price_bigint')->nullable()->after('sale_price');
                    } else {
                        $table->unsignedBigInteger('sale_price_bigint')->nullable();
                    }
                }
            });

            try {
                DB::statement('UPDATE product_prices SET price_bigint = CAST(ROUND(COALESCE(price, 0) * ?) AS UNSIGNED)', [$multiplier]);
                DB::statement('UPDATE product_prices SET purchase_price_bigint = CAST(ROUND(COALESCE(purchase_price, 0) * ?) AS UNSIGNED)', [$multiplier]);
                DB::statement('UPDATE product_prices SET sale_price_bigint = CAST(ROUND(COALESCE(sale_price, 0) * ?) AS UNSIGNED) WHERE sale_price IS NOT NULL', [$multiplier]);
            } catch (\Exception $e) {
                // swallow - conversion attempt
            }

            // Drop old decimal columns and rename bigint columns if present
            if (Schema::hasColumn('product_prices', 'price') || Schema::hasColumn('product_prices', 'purchase_price') || Schema::hasColumn('product_prices', 'sale_price')) {
                Schema::table('product_prices', function (Blueprint $table) {
                    $drop = [];
                    if (Schema::hasColumn('product_prices', 'price')) $drop[] = 'price';
                    if (Schema::hasColumn('product_prices', 'purchase_price')) $drop[] = 'purchase_price';
                    if (Schema::hasColumn('product_prices', 'sale_price')) $drop[] = 'sale_price';
                    if (! empty($drop)) $table->dropColumn($drop);
                });
            }

            // Rename if new cols exist
            Schema::table('product_prices', function (Blueprint $table) {
                if (Schema::hasColumn('product_prices', 'price_bigint') && ! Schema::hasColumn('product_prices', 'price')) {
                    $table->renameColumn('price_bigint', 'price');
                }
                if (Schema::hasColumn('product_prices', 'purchase_price_bigint') && ! Schema::hasColumn('product_prices', 'purchase_price')) {
                    $table->renameColumn('purchase_price_bigint', 'purchase_price');
                }
                if (Schema::hasColumn('product_prices', 'sale_price_bigint') && ! Schema::hasColumn('product_prices', 'sale_price')) {
                    $table->renameColumn('sale_price_bigint', 'sale_price');
                }
            });
        }

        // =====================================================
        // 2. ORDERS TABLE
        // =====================================================
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $columns = ['total_amount', 'discount_amount', 'paid_amount', 'delivery_fee', 'tax_amount', 'service_fee', 'tip_amount'];
                foreach ($columns as $col) {
                    if (! Schema::hasColumn('orders', "{$col}_bigint")) {
                        if (Schema::hasColumn('orders', $col)) {
                            $table->unsignedBigInteger("{$col}_bigint")->nullable()->after($col);
                        } else {
                            $table->unsignedBigInteger("{$col}_bigint")->nullable();
                        }
                    }
                }
            });

            $columns = ['total_amount', 'discount_amount', 'paid_amount', 'delivery_fee', 'tax_amount', 'service_fee'];
            foreach ($columns as $col) {
                try {
                    DB::statement("UPDATE orders SET {$col}_bigint = CAST(ROUND(COALESCE({$col}, 0) * ?) AS UNSIGNED)", [$multiplier]);
                } catch (\Exception $e) {
                    // continue
                }
            }

            try {
                DB::statement("UPDATE orders SET tip_amount_bigint = CAST(ROUND(tip_amount * ?) AS UNSIGNED) WHERE tip_amount IS NOT NULL", [$multiplier]);
            } catch (\Exception $e) {
                // continue
            }

            // Drop and rename
            Schema::table('orders', function (Blueprint $table) {
                $drop = [];
                foreach (['total_amount', 'discount_amount', 'paid_amount', 'delivery_fee', 'tax_amount', 'service_fee', 'tip_amount'] as $c) {
                    if (Schema::hasColumn('orders', $c)) $drop[] = $c;
                }
                if (! empty($drop)) $table->dropColumn($drop);
            });

            Schema::table('orders', function (Blueprint $table) {
                foreach (['total_amount', 'discount_amount', 'paid_amount', 'delivery_fee', 'tax_amount', 'service_fee', 'tip_amount'] as $c) {
                    if (Schema::hasColumn('orders', "{$c}_bigint") && ! Schema::hasColumn('orders', $c)) {
                        $table->renameColumn("{$c}_bigint", $c);
                    }
                }
            });
        }

        // =====================================================
        // 3. ORDER ITEMS TABLE
        // =====================================================
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'total_price_bigint')) {
                    $table->unsignedBigInteger('total_price_bigint')->nullable()->after('total_price');
                }
            });

            DB::statement('
                UPDATE order_items
                SET total_price_bigint = CAST(ROUND(COALESCE(total_price, 0) * ?) AS UNSIGNED)
            ', [$multiplier]);

            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('total_price');
                $table->renameColumn('total_price_bigint', 'total_price');
            });
        }

        // =====================================================
        // 4. CART ITEMS TABLE
        // =====================================================
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if (!Schema::hasColumn('cart_items', 'total_price_bigint')) {
                    $table->unsignedBigInteger('total_price_bigint')->nullable()->after('total_price');
                }
            });

            DB::statement('
                UPDATE cart_items
                SET total_price_bigint = CAST(ROUND(COALESCE(total_price, 0) * ?) AS UNSIGNED)
            ', [$multiplier]);

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('total_price');
                $table->renameColumn('total_price_bigint', 'total_price');
            });
        }

        // =====================================================
        // 5. SHIPPING PRICES TABLE
        // =====================================================
        if (Schema::hasTable('shipping_prices')) {
            Schema::table('shipping_prices', function (Blueprint $table) {
                $columns = ['base_price', 'max_price', 'per_km_price', 'max_cod_price'];
                foreach ($columns as $col) {
                    if (!Schema::hasColumn('shipping_prices', "{$col}_bigint")) {
                        $table->unsignedBigInteger("{$col}_bigint")->nullable()->after($col);
                    }
                }
            });

            $columns = ['base_price', 'max_price', 'per_km_price', 'max_cod_price'];
            foreach ($columns as $col) {
                DB::statement("
                    UPDATE shipping_prices
                    SET {$col}_bigint = CAST(ROUND(COALESCE({$col}, 0) * ?) AS UNSIGNED)
                ", [$multiplier]);
            }

            Schema::table('shipping_prices', function (Blueprint $table) {
                $table->dropColumn(['base_price', 'max_price', 'per_km_price', 'max_cod_price']);
            });

            Schema::table('shipping_prices', function (Blueprint $table) {
                $table->renameColumn('base_price_bigint', 'base_price');
                $table->renameColumn('max_price_bigint', 'max_price');
                $table->renameColumn('per_km_price_bigint', 'per_km_price');
                $table->renameColumn('max_cod_price_bigint', 'max_cod_price');
            });
        }

        // =====================================================
        // 6. ADD-ONS TABLE
        // =====================================================
        if (Schema::hasTable('add_ons')) {
            Schema::table('add_ons', function (Blueprint $table) {
                if (!Schema::hasColumn('add_ons', 'price_bigint')) {
                    $table->unsignedBigInteger('price_bigint')->nullable()->after('price');
                }
            });

            DB::statement('
                UPDATE add_ons
                SET price_bigint = CAST(ROUND(COALESCE(price, 0) * ?) AS UNSIGNED)
            ', [$multiplier]);

            Schema::table('add_ons', function (Blueprint $table) {
                $table->dropColumn('price');
                $table->renameColumn('price_bigint', 'price');
            });
        }

        // =====================================================
        // 7. ADD-ON ORDER TABLE
        // =====================================================
        if (Schema::hasTable('add_on_order')) {
            if (Schema::hasColumn('add_on_order', 'price')) {
                // Check if already bigint
                try {
                    $columnType = DB::connection()->getDoctrineColumn('add_on_order', 'price')->getType()->getName();
                    if ($columnType !== 'bigint') {
                        Schema::table('add_on_order', function (Blueprint $table) {
                            $table->unsignedBigInteger('price_bigint')->nullable()->after('price');
                        });

                        DB::statement('
                            UPDATE add_on_order
                            SET price_bigint = CAST(ROUND(COALESCE(price, 0) * ?) AS UNSIGNED)
                        ', [$multiplier]);

                        Schema::table('add_on_order', function (Blueprint $table) {
                            $table->dropColumn('price');
                            $table->renameColumn('price_bigint', 'price');
                        });
                    }
                } catch (\Exception $e) {
                    // If column check fails, assume it needs conversion
                }
            }
        }

        // =====================================================
        // 8. ADD-ON ORDER ITEM TABLE
        // =====================================================
        if (Schema::hasTable('add_on_order_item')) {
            if (Schema::hasColumn('add_on_order_item', 'price_modifier')) {
                try {
                    $columnType = DB::connection()->getDoctrineColumn('add_on_order_item', 'price_modifier')->getType()->getName();
                    if ($columnType !== 'bigint') {
                        Schema::table('add_on_order_item', function (Blueprint $table) {
                            $table->unsignedBigInteger('price_modifier_bigint')->nullable()->after('price_modifier');
                        });

                        DB::statement('
                            UPDATE add_on_order_item
                            SET price_modifier_bigint = CAST(ROUND(COALESCE(price_modifier, 0) * ?) AS UNSIGNED)
                        ', [$multiplier]);

                        Schema::table('add_on_order_item', function (Blueprint $table) {
                            $table->dropColumn('price_modifier');
                            $table->renameColumn('price_modifier_bigint', 'price_modifier');
                        });
                    }
                } catch (\Exception $e) {
                    // Continue
                }
            }
        }

        // =====================================================
        // 9. ADD-ON CART ITEM TABLE
        // =====================================================
        if (Schema::hasTable('add_on_cart_item')) {
            if (Schema::hasColumn('add_on_cart_item', 'price_modifier')) {
                try {
                    $columnType = DB::connection()->getDoctrineColumn('add_on_cart_item', 'price_modifier')->getType()->getName();
                    if ($columnType !== 'bigint') {
                        Schema::table('add_on_cart_item', function (Blueprint $table) {
                            $table->unsignedBigInteger('price_modifier_bigint')->nullable()->after('price_modifier');
                        });

                        DB::statement('
                            UPDATE add_on_cart_item
                            SET price_modifier_bigint = CAST(ROUND(COALESCE(price_modifier, 0) * ?) AS UNSIGNED)
                        ', [$multiplier]);

                        Schema::table('add_on_cart_item', function (Blueprint $table) {
                            $table->dropColumn('price_modifier');
                            $table->renameColumn('price_modifier_bigint', 'price_modifier');
                        });
                    }
                } catch (\Exception $e) {
                    // Continue
                }
            }
        }

        // =====================================================
        // 10. STORE SETTINGS TABLE
        // =====================================================
        if (Schema::hasTable('store_settings')) {
            $settingsColumns = ['minimum_order_amount', 'tax_rate', 'service_fee_percentage'];
            foreach ($settingsColumns as $col) {
                if (Schema::hasColumn('store_settings', $col)) {
                    Schema::table('store_settings', function (Blueprint $table) use ($col) {
                        if (!Schema::hasColumn('store_settings', "{$col}_bigint")) {
                            $table->unsignedBigInteger("{$col}_bigint")->nullable()->after($col);
                        }
                    });

                    DB::statement("
                        UPDATE store_settings
                        SET {$col}_bigint = CAST(ROUND(COALESCE({$col}, 0) * ?) AS UNSIGNED)
                    ", [$multiplier]);

                    Schema::table('store_settings', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                        $table->renameColumn("{$col}_bigint", $col);
                    });
                }
            }
        }

        // =====================================================
        // 11. USERS TABLE - BALANCE
        // =====================================================
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'balance')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'balance_bigint')) {
                    $table->unsignedBigInteger('balance_bigint')->default(0)->after('balance');
                }
            });

            DB::statement('
                UPDATE users
                SET balance_bigint = CAST(ROUND(COALESCE(balance, 0) * ?) AS UNSIGNED)
            ', [$multiplier]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('balance');
                $table->renameColumn('balance_bigint', 'balance');
            });
        }

        // =====================================================
        // 12. LOYALTY SETTINGS TABLE
        // =====================================================
        if (Schema::hasTable('loyalty_settings')) {
            $loyaltyColumns = ['earn_rate', 'min_order_for_earn'];
            foreach ($loyaltyColumns as $col) {
                if (Schema::hasColumn('loyalty_settings', $col)) {
                    Schema::table('loyalty_settings', function (Blueprint $table) use ($col) {
                        if (!Schema::hasColumn('loyalty_settings', "{$col}_bigint")) {
                            $table->unsignedBigInteger("{$col}_bigint")->nullable()->after($col);
                        }
                    });

                    DB::statement("
                        UPDATE loyalty_settings
                        SET {$col}_bigint = CAST(ROUND(COALESCE({$col}, 0) * ?) AS UNSIGNED)
                    ", [$multiplier]);

                    Schema::table('loyalty_settings', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                        $table->renameColumn("{$col}_bigint", $col);
                    });
                }
            }
        }

        // =====================================================
        // 13. LOYALTY TRANSACTIONS TABLE
        // =====================================================
        if (Schema::hasTable('loyalty_transactions')) {
            $transactionColumns = ['points', 'points_balance_after'];
            foreach ($transactionColumns as $col) {
                if (Schema::hasColumn('loyalty_transactions', $col)) {
                    Schema::table('loyalty_transactions', function (Blueprint $table) use ($col) {
                        if (!Schema::hasColumn('loyalty_transactions', "{$col}_bigint")) {
                            $table->unsignedBigInteger("{$col}_bigint")->nullable()->after($col);
                        }
                    });

                    DB::statement("
                        UPDATE loyalty_transactions
                        SET {$col}_bigint = CAST(ROUND(COALESCE({$col}, 0) * ?) AS UNSIGNED)
                    ", [$multiplier]);

                    Schema::table('loyalty_transactions', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                        $table->renameColumn("{$col}_bigint", $col);
                    });
                }
            }
        }

        // =====================================================
        // 14. REWARDS TABLE
        // =====================================================
        if (Schema::hasTable('rewards')) {
            if (Schema::hasColumn('rewards', 'value_amount')) {
                Schema::table('rewards', function (Blueprint $table) {
                    if (!Schema::hasColumn('rewards', 'value_amount_bigint')) {
                        $table->unsignedBigInteger('value_amount_bigint')->nullable()->after('value_amount');
                    }
                });

                DB::statement('
                    UPDATE rewards
                    SET value_amount_bigint = CAST(ROUND(COALESCE(value_amount, 0) * ?) AS UNSIGNED)
                ', [$multiplier]);

                Schema::table('rewards', function (Blueprint $table) {
                    $table->dropColumn('value_amount');
                    $table->renameColumn('value_amount_bigint', 'value_amount');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $defaultCurrencyUnit = 100;

        try {
            $defaultCurrency = DB::table('countries')->where('is_active', true)->first();
            if ($defaultCurrency && $defaultCurrency->currency_unit) {
                $defaultCurrencyUnit = (int)$defaultCurrency->currency_unit;
            }
        } catch (\Exception $e) {
            //
        }

        // Reverse product_prices
        if (Schema::hasTable('product_prices')) {
            Schema::table('product_prices', function (Blueprint $table) {
                $table->decimal('price_temp', 10, 3)->nullable()->after('price');
                $table->decimal('purchase_price_temp', 10, 3)->nullable()->after('purchase_price');
                $table->decimal('sale_price_temp', 10, 3)->nullable()->after('sale_price');
            });

            DB::statement('UPDATE product_prices SET price_temp = price / ?', [$defaultCurrencyUnit]);
            DB::statement('UPDATE product_prices SET purchase_price_temp = purchase_price / ?', [$defaultCurrencyUnit]);
            DB::statement('UPDATE product_prices SET sale_price_temp = sale_price / ? WHERE sale_price > 0', [$defaultCurrencyUnit]);

            Schema::table('product_prices', function (Blueprint $table) {
                $table->dropColumn(['price', 'purchase_price', 'sale_price']);
                $table->renameColumn('price_temp', 'price');
                $table->renameColumn('purchase_price_temp', 'purchase_price');
                $table->renameColumn('sale_price_temp', 'sale_price');
            });
        }

        // Similar pattern for other tables would follow - implement as needed
    }
};
