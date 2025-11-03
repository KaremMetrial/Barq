<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all models for permission generation
        $models = [
            // App Models
            'attachment', 'loyalty_setting', 'loyalty_transaction', 'national_identity',
            'plan', 'report', 'seo', 'shipping_price', 'sms_provider', 'sms_setting',
            'subscription', 'transaction',

            // Module Models
            'ad', 'addon', 'address', 'balance', 'banner', 'cart', 'cart_item', 'category',
            'city', 'compaign', 'compaign_participation', 'contact_us', 'conversation',
            'couier', 'couier_shift', 'couier_vehicle', 'country', 'coupon',
            'delivery_instruction', 'favourite', 'governorate', 'interest', 'language',
            'message', 'offer', 'option', 'order', 'order_item', 'order_proof',
            'order_status_history', 'otp', 'page', 'pharmacy_info', 'pos_shift',
            'pos_terminal', 'product', 'product_allergen', 'product_availability',
            'product_image', 'product_nutrition', 'product_option', 'product_option_value',
            'product_price', 'product_price_sale', 'product_value', 'product_watermarks',
            'review', 'role', 'search', 'section', 'setting', 'store', 'store_setting',
            'tag', 'unit', 'user', 'vehicle', 'vendor', 'working_day', 'zone',
        ];

        // Generate CRUD permissions for each model
        $permissions = [];
        foreach ($models as $model) {
            $permissions[] = "view_{$model}";
            $permissions[] = "create_{$model}";
            $permissions[] = "update_{$model}";
            $permissions[] = "delete_{$model}";
        }

        // Add legacy permissions
        $legacyPermissions = [
            'manage_store', 'store_owner', 'manage_products', 'manage_orders',
            'manage_inventory', 'view_reports', 'manage_staff', 'process_payments',
            'handle_customer_service',
        ];

        $permissions = array_merge($permissions, $legacyPermissions);

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // Create permissions for vendor guard
        $vendorPermissions = [
            'manage_store', 'store_owner', 'manage_products', 'manage_orders',
            'manage_inventory', 'view_reports', 'manage_staff', 'process_payments',
            'handle_customer_service',
            // Store management
            'view_store', 'update_store', 'view_store_setting', 'update_store_setting',
            // Products
            'view_product', 'create_product', 'update_product', 'delete_product',
            'view_product_image', 'create_product_image', 'update_product_image', 'delete_product_image',
            'view_product_price', 'create_product_price', 'update_product_price', 'delete_product_price',
            'view_product_option', 'create_product_option', 'update_product_option', 'delete_product_option',
            'view_product_option_value', 'create_product_option_value', 'update_product_option_value', 'delete_product_option_value',
            'view_product_availability', 'create_product_availability', 'update_product_availability', 'delete_product_availability',
            'view_product_nutrition', 'create_product_nutrition', 'update_product_nutrition', 'delete_product_nutrition',
            'view_product_allergen', 'create_product_allergen', 'update_product_allergen', 'delete_product_allergen',
            'view_product_value', 'create_product_value', 'update_product_value', 'delete_product_value',
            'view_product_watermarks', 'create_product_watermarks', 'update_product_watermarks', 'delete_product_watermarks',
            // Orders
            'view_order', 'update_order', 'view_order_item', 'update_order_item',
            'view_order_status_history', 'create_order_status_history',
            'view_order_proof', 'create_order_proof', 'update_order_proof', 'delete_order_proof',
            // Categories and sections
            'view_category', 'create_category', 'update_category', 'delete_category',
            'view_section', 'create_section', 'update_section', 'delete_section',
            // Reviews and reports
            'view_review', 'update_review', 'delete_review',
            'view_report', 'create_report', 'update_report', 'delete_report',
            // Staff management
            'view_user', 'create_user', 'update_user', 'delete_user',
            'view_role', 'create_role', 'update_role', 'delete_role',
            // Other store features
            'view_coupon', 'create_coupon', 'update_coupon', 'delete_coupon',
            'view_offer', 'create_offer', 'update_offer', 'delete_offer',
            'view_banner', 'create_banner', 'update_banner', 'delete_banner',
            'view_addon', 'create_addon', 'update_addon', 'delete_addon',
            'view_option', 'create_option', 'update_option', 'delete_option',
            'view_tag', 'create_tag', 'update_tag', 'delete_tag',
            'view_unit', 'create_unit', 'update_unit', 'delete_unit',
        ];

        foreach ($vendorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'vendor']);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Store Owner role
        $storeOwnerRole = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'vendor']);
        $storeOwnerRole->syncPermissions($vendorPermissions);

        // Store Employee role
        $storeEmployeeRole = Role::firstOrCreate(['name' => 'store_employee', 'guard_name' => 'vendor']);
        $storeEmployeePermissions = [
            'manage_products', 'manage_orders', 'manage_inventory', 'process_payments', 'handle_customer_service',
            // Product management
            'view_product', 'create_product', 'update_product',
            'view_product_image', 'create_product_image', 'update_product_image',
            'view_product_price', 'create_product_price', 'update_product_price',
            'view_product_option', 'create_product_option', 'update_product_option',
            'view_product_option_value', 'create_product_option_value', 'update_product_option_value',
            'view_product_availability', 'create_product_availability', 'update_product_availability',
            // Order management
            'view_order', 'update_order', 'view_order_item', 'update_order_item',
            'view_order_status_history', 'create_order_status_history',
            // Reviews
            'view_review', 'update_review',
            // Basic store features
            'view_coupon', 'view_offer', 'view_banner',
        ];
        $storeEmployeeRole->syncPermissions($storeEmployeePermissions);

        // Cashier role
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'vendor']);
        $cashierPermissions = [
            'manage_orders', 'process_payments', 'handle_customer_service',
            'view_order', 'update_order', 'view_order_item',
            'view_order_status_history', 'create_order_status_history',
            'view_product', 'view_product_price', 'view_product_option', 'view_product_option_value',
            'view_coupon', 'view_offer',
        ];
        $cashierRole->syncPermissions($cashierPermissions);

        // Vendor role
        $vendorRole = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'vendor']);
        $vendorPermissions = [
            'view_store', 'update_store',
            'view_product', 'create_product', 'update_product', 'delete_product',
            'view_product_image', 'create_product_image', 'update_product_image', 'delete_product_image',
            'view_product_price', 'create_product_price', 'update_product_price', 'delete_product_price',
            'view_order', 'update_order', 'view_order_item',
            'view_report', 'view_review',
        ];
        $vendorRole->syncPermissions($vendorPermissions);

        // Create permissions for user guard
        $userPermissions = [
            'view_product', 'view_category', 'view_section', 'view_store',
            'view_cart', 'create_cart', 'update_cart', 'delete_cart',
            'view_cart_item', 'create_cart_item', 'update_cart_item', 'delete_cart_item',
            'view_order', 'create_order', 'update_order',
            'view_order_item', 'update_order_item',
            'view_favourite', 'create_favourite', 'delete_favourite',
            'view_address', 'create_address', 'update_address', 'delete_address',
            'view_review', 'create_review', 'update_review',
            'view_coupon', 'view_offer', 'view_banner',
            'view_order_status_history', 'create_order_status_history',
            'view_delivery_instruction', 'view_user',
        ];

        foreach ($userPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'user']);
        }

        // Customer role
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'user']);
        $customerPermissions = [
            'view_product', 'view_category', 'view_section', 'view_store',
            'view_cart', 'create_cart', 'update_cart', 'delete_cart',
            'view_cart_item', 'create_cart_item', 'update_cart_item', 'delete_cart_item',
            'view_order', 'create_order',
            'view_favourite', 'create_favourite', 'delete_favourite',
            'view_address', 'create_address', 'update_address', 'delete_address',
            'view_review', 'create_review', 'update_review',
            'view_coupon', 'view_offer', 'view_banner',
        ];
        $customerRole->syncPermissions($customerPermissions);

        // Courier role
        $courierRole = Role::firstOrCreate(['name' => 'courier', 'guard_name' => 'user']);
        $courierPermissions = [
            'view_order', 'update_order', 'view_order_item',
            'view_order_status_history', 'create_order_status_history',
            'view_delivery_instruction',
            'view_address', 'view_user',
        ];
        $courierRole->syncPermissions($courierPermissions);
    }
}
