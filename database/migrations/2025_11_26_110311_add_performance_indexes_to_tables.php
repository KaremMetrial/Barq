<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasIndex('orders', 'idx_orders_status')) {
                $table->index('status', 'idx_orders_status');
            }
            if (!Schema::hasIndex('orders', 'idx_orders_created_at')) {
                $table->index('created_at', 'idx_orders_created_at');
            }
            if (!Schema::hasIndex('orders', 'idx_orders_store_status')) {
                $table->index(['store_id', 'status'], 'idx_orders_store_status');
            }
            if (!Schema::hasIndex('orders', 'idx_orders_user_status')) {
                $table->index(['user_id', 'status'], 'idx_orders_user_status');
            }
            if (!Schema::hasIndex('orders', 'idx_orders_payment_status')) {
                $table->index('payment_status', 'idx_orders_payment_status');
            }
        });

        // Conversations table indexes
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasIndex('conversations', 'idx_conversations_end_time')) {
                $table->index('end_time', 'idx_conversations_end_time');
            }
            if (!Schema::hasIndex('conversations', 'idx_conversations_user_active')) {
                $table->index(['user_id', 'end_time'], 'idx_conversations_user_active');
            }
            if (!Schema::hasIndex('conversations', 'idx_conversations_admin_active')) {
                $table->index(['admin_id', 'end_time'], 'idx_conversations_admin_active');
            }
        });

        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'idx_products_store_id')) {
                $table->index('store_id', 'idx_products_store_id');
            }
            if (!Schema::hasIndex('products', 'idx_products_category_id')) {
                $table->index('category_id', 'idx_products_category_id');
            }
            // Removed is_available as it doesn't exist on products table
        });

        // Product Availabilities indexes
        if (Schema::hasTable('product_availabilities')) {
            Schema::table('product_availabilities', function (Blueprint $table) {
                if (!Schema::hasIndex('product_availabilities', 'idx_prod_avail_stock')) {
                    $table->index(['is_in_stock', 'stock_quantity'], 'idx_prod_avail_stock');
                }
            });
        }

        // Carts table indexes
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasIndex('carts', 'idx_carts_cart_key')) {
                $table->index('cart_key', 'idx_carts_cart_key');
            }
            if (!Schema::hasIndex('carts', 'idx_carts_user_id')) {
                $table->index('user_id', 'idx_carts_user_id');
            }
        });

        // Stores table indexes
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasIndex('stores', 'idx_stores_is_active')) {
                $table->index('is_active', 'idx_stores_is_active');
            }
            if (!Schema::hasIndex('stores', 'idx_stores_status')) {
                $table->index('status', 'idx_stores_status');
            }
            if (!Schema::hasIndex('stores', 'idx_stores_active_approved')) {
                $table->index(['is_active', 'status'], 'idx_stores_active_approved');
            }
        });

        // Messages table indexes
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasIndex('messages', 'idx_messages_conversation_time')) {
                $table->index(['conversation_id', 'created_at'], 'idx_messages_conversation_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['idx_orders_status']);
            $table->dropIndex(['idx_orders_created_at']);
            $table->dropIndex(['idx_orders_store_status']);
            $table->dropIndex(['idx_orders_user_status']);
            $table->dropIndex(['idx_orders_payment_status']);
        });

        // Conversations table
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['idx_conversations_end_time']);
            $table->dropIndex(['idx_conversations_user_active']);
            $table->dropIndex(['idx_conversations_admin_active']);
        });

        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['idx_products_store_id']);
            $table->dropIndex(['idx_products_category_id']);
        });

        // Product Availabilities
        if (Schema::hasTable('product_availabilities')) {
            Schema::table('product_availabilities', function (Blueprint $table) {
                $table->dropIndex(['idx_prod_avail_stock']);
            });
        }

        // Carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['idx_carts_cart_key']);
            $table->dropIndex(['idx_carts_user_id']);
        });

        // Stores table
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['idx_stores_is_active']);
            $table->dropIndex(['idx_stores_status']);
            $table->dropIndex(['idx_stores_active_approved']);
        });

        // Messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['idx_messages_conversation_time']);
        });
    }
};
