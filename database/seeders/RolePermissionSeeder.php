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
        // Create permissions
        $permissions = [
            // Vendor permissions
            'manage_store',
            'manage_products',
            'manage_orders',
            'manage_inventory',
            'view_reports',
            'manage_staff',
            'process_payments',
            'handle_customer_service',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $storeOwnerRole = Role::firstOrCreate(['name' => 'store_owner']);
        $storeOwnerRole->syncPermissions($permissions); // Store owner has all permissions

        $storeEmployeeRole = Role::firstOrCreate(['name' => 'store_employee']);
        $storeEmployeeRole->syncPermissions([
            'manage_products',
            'manage_orders',
            'manage_inventory',
            'process_payments',
            'handle_customer_service',
        ]);

        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierRole->syncPermissions([
            'manage_orders',
            'process_payments',
            'handle_customer_service',
        ]);
    }
}
