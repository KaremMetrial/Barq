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
            'coupon_usage',
            'plan',
            'rating_key',
            'report',
            'review_rating',
            'seo',
            // 'sms_provider',
            // 'sms_setting',
            'subscription',
            'transaction',

            // Module Models
            // 'ad',
            'addon',
            // 'address',
            // 'balance',
            'banner',
            'category',
            'city',
            // 'compaign',
            // 'compaign_participation',
            'contact_us',
            'conversation',
            'couier',
            'couier_shift',
            'couier_vehicle',
            'courier_order_assignment',
            'country',
            'coupon',
            // 'delivery_instruction',
            'governorate',
            // 'language',
            'loyalty_setting',
            'loyalty_transaction',
            // 'message',
            'offer',
            'option',
            'order',
            // 'order_item',
            // 'order_proof',
            // 'order_status_history',
            'page',
            'payment_method',
            'pos_shift',
            'pos_terminal',
            'product',
            'review',
            'reward',
            'role',
            'section',
            'setting',
            'shipping_price',
            'shift_template',
            'shift_template_day',
            'store',
            'user',
            'vehicle',
            'vendor',
            'withdrawal',
            'working_day',
            'zone',
            'admin',
            'delivery_company'
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

        // Create all permissions for admin guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // ===== ADMIN ROLES =====

        // SUPER ADMIN ROLE - Has all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'admin']);
        $superAdminRole->syncPermissions($permissions);

        // DATA ENTRY ROLE - Can manage master data
        $dataEntryRole = Role::firstOrCreate(['name' => 'data_entry', 'guard_name' => 'admin']);
        $dataEntryPermissions = [
            // User management
            'view_user', 'create_user', 'update_user', 'delete_user',
            // Product management
            'view_product', 'create_product', 'update_product', 'delete_product',
            // Categories and sections
            'view_category', 'create_category', 'update_category', 'delete_category',
            'view_section', 'create_section', 'update_section', 'delete_section',
            // Basic store features
            'view_store',
            'view_coupon', 'create_coupon', 'update_coupon', 'delete_coupon',
            'view_offer', 'create_offer', 'update_offer', 'delete_offer',
            'view_banner', 'create_banner', 'update_banner', 'delete_banner',
            'view_addon', 'create_addon', 'update_addon', 'delete_addon',
            'view_option', 'create_option', 'update_option', 'delete_option',
            // Location data
            'view_city', 'view_governorate', 'view_country', 'view_zone',
            // Content management
            'view_page', 'create_page', 'update_page', 'delete_page',
            'view_setting', 'update_setting',
            // Loyalty and rewards
            'view_loyalty_setting', 'update_loyalty_setting',
            'view_loyalty_transaction', 'view_reward',
            // POS and terminals
            'view_pos_shift', 'view_pos_terminal',
        ];
        $dataEntryRole->syncPermissions($dataEntryPermissions);





        $dataEntryPermissions = [
            'view_addon', 'create_addon', 'update_addon',
            'view_city', 'view_governorate', 'view_country', 'view_zone',
            'view_option', 'create_option', 'update_option',
            // Product management
            'view_product', 'create_product', 'update_product',
            'view_vendor', 'create_vendor', 'update_vendor',
            // Categories and sections
            'view_category', 'create_category', 'update_category', 'delete_category',
            'view_section', 'create_section', 'update_section', 'delete_section',
            // Basic store features
            'view_store', 'create_store', 'update_store',
            'view_pos_shift', 'create_pos_shift', 'update_pos_shift',
            'view_pos_terminal', 'create_pos_terminal', 'update_pos_terminal',
            'manage_store', 'manage_products' ,'store_owner' ,'manage_staff',
            'view_setting'
        ];
        $dataEntryRole->syncPermissions($dataEntryPermissions);




        // COURIER ADMIN ROLE - Manages couriers and deliveries
        $courierAdminRole = Role::firstOrCreate(['name' => 'courier_admin', 'guard_name' => 'admin']);
        $courierAdminPermissions = [
            // Courier management
            'view_couier', 'create_couier', 'update_couier', 'delete_couier',
            'view_vehicle', 'create_vehicle', 'update_vehicle', 'delete_vehicle',
            'view_couier_shift', 'create_couier_shift', 'update_couier_shift', 'delete_couier_shift',
            'view_couier_vehicle', 'create_couier_vehicle', 'update_couier_vehicle', 'delete_couier_vehicle',
            'view_courier_order_assignment', 'create_courier_order_assignment', 'update_courier_order_assignment', 'delete_courier_order_assignment',
            'view_shift_template', 'create_shift_template', 'update_shift_template', 'delete_shift_template',
            'view_shift_template_day', 'create_shift_template_day', 'update_shift_template_day', 'delete_shift_template_day',
            // Order management (delivery focused)
            'view_order', 'update_order',
            // 'view_delivery_instruction',
            // Location data for delivery zones
            'view_city', 'view_governorate', 'view_country', 'view_zone',
            // Reports
            'view_report',
            // Working days
            'view_working_day', 'create_working_day', 'update_working_day', 'delete_working_day',
        ];
        $courierAdminRole->syncPermissions($courierAdminPermissions);

        // SALES ROLE - View-only access for analytics and reports
        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'admin']);
        $salesPermissions = [
            // View permissions for analysis
            'view_order', 'view_product', 'view_store',
            'view_category', 'view_section', 'view_city', 'view_governorate', 'view_country',
            'view_coupon', 'view_offer', 'view_banner', 'view_review', 'view_report',
            'view_transaction', 'view_subscription',
            // Reviews and ratings
            'view_rating_key',
            // Rewards
            'view_reward',
            // Legacy permissions
            'view_reports', 'manage_inventory',
        ];
        $salesRole->syncPermissions($salesPermissions);

        // ===== VENDOR ROLES =====

        // Create permissions for vendor guard
        $vendorCrudModels = [
            'store', 'product', 'order', 'order_proof', 'category', 'section',
            'review', 'report', 'user', 'role', 'coupon', 'offer', 'banner',
            'addon', 'option', 'loyalty_setting', 'loyalty_transaction', 'reward',
            'pos_shift', 'pos_terminal'
        ];

        $vendorCrudPermissions = [];
        foreach ($vendorCrudModels as $model) {
            $vendorCrudPermissions[] = "view_{$model}";
            $vendorCrudPermissions[] = "create_{$model}";
            $vendorCrudPermissions[] = "update_{$model}";
            $vendorCrudPermissions[] = "delete_{$model}";
        }

        // Legacy vendor permissions
        $legacyVendorPermissions = [
            'manage_store', 'store_owner', 'manage_products', 'manage_orders',
            'manage_inventory', 'view_reports', 'manage_staff', 'process_payments',
            'handle_customer_service',
        ];

        $vendorPermissions = array_merge($vendorCrudPermissions, $legacyVendorPermissions);

        foreach ($vendorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'vendor']);
        }

        // Store Owner role
        $storeOwnerRole = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'vendor']);
        $storeOwnerRole->syncPermissions($vendorPermissions);

        // Store Employee role
        $storeEmployeeRole = Role::firstOrCreate(['name' => 'store_employee', 'guard_name' => 'vendor']);
        $storeEmployeePermissions = [
            'manage_products', 'manage_orders', 'manage_inventory', 'process_payments', 'handle_customer_service',
            // Product management
            'view_product', 'create_product', 'update_product',
            // Order management
            'view_order', 'update_order',
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
            'view_order', 'update_order',
            'view_product',
            'view_coupon', 'view_offer',
        ];
        $cashierRole->syncPermissions($cashierPermissions);

        // Vendor role
        $vendorRole = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'vendor']);
        $vendorRolePermissions = [
            'view_store', 'update_store',
            'view_product', 'create_product', 'update_product', 'delete_product',
            'view_order', 'update_order',
            'view_report', 'view_review',
        ];
        $vendorRole->syncPermissions($vendorRolePermissions);

        // ===== USER ROLES =====

        // Create permissions for user guard
        $userCrudModels = [
            'cart', 'favourite', 'order', 'review', 'withdrawal', 'conversation'
        ];

        $userCrudPermissions = [];
        foreach ($userCrudModels as $model) {
            $userCrudPermissions[] = "view_{$model}";
            $userCrudPermissions[] = "create_{$model}";
            $userCrudPermissions[] = "update_{$model}";
            $userCrudPermissions[] = "delete_{$model}";
        }

        // View-only permissions for users
        $userViewPermissions = [
            'view_product', 'view_category', 'view_section', 'view_store',
            'view_coupon', 'view_offer', 'view_banner',
            'view_transaction', 'view_loyalty_transaction', 'view_reward',
            'view_otp', 'view_interest', 'view_language', 'view_working_day',
            'view_subscription', 'view_comcampaign', 'view_ad', 'view_contact_us',
        ];

        $userPermissions = array_merge($userCrudPermissions, $userViewPermissions);

        foreach ($userPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'user']);
        }

        // Customer role
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'user']);
        $customerPermissions = [
            'view_product', 'view_category', 'view_section', 'view_store',
            'view_cart', 'create_cart', 'update_cart', 'delete_cart',
            'view_order', 'create_order',
            'view_favourite', 'create_favourite', 'delete_favourite',
            'view_review', 'create_review', 'update_review',
            'view_coupon', 'view_offer', 'view_banner',
        ];
        $customerRole->syncPermissions($customerPermissions);

        // Courier role
        $courierRole = Role::firstOrCreate(['name' => 'courier', 'guard_name' => 'user']);
        $courierPermissions = [
            'view_order', 'update_order',
        ];
        $courierRole->syncPermissions($courierPermissions);
    }
}
