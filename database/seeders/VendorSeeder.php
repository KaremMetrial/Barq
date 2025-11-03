<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Modules\Vendor\Models\Vendor;
use Spatie\Permission\Models\Role;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = DB::table('stores')->get();

        foreach ($stores as $store) {
            $vendor = Vendor::create([
                'first_name' => 'مدير',
                'last_name' => $store->id == 1 ? 'ماكدونالدز' : 'بيتزا هت',
                'email' => 'vendor' . $store->id . '@example.com',
                'phone' => '123456789' . $store->id,
                'password' => Hash::make('password123'),
                'is_owner' => true,
                'is_active' => true,
                'store_id' => $store->id,
            ]);

            // Assign store_owner role to the vendor
            $storeOwnerRole = Role::where('name', 'store_owner')->where('guard_name', 'vendor')->first();
            if ($storeOwnerRole) {
                $vendor->assignRole($storeOwnerRole);
            }
        }

        // Create additional vendors with different roles
        $storeOwnerRole = Role::where('name', 'store_owner')->where('guard_name', 'vendor')->first();
        $storeEmployeeRole = Role::where('name', 'store_employee')->where('guard_name', 'vendor')->first();
        $cashierRole = Role::where('name', 'cashier')->where('guard_name', 'vendor')->first();

        // Create a store employee
        $employee = Vendor::create([
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'employee@example.com',
            'phone' => '1111111111',
            'password' => Hash::make('password123'),
            'is_owner' => false,
            'is_active' => true,
            'store_id' => 1,
        ]);
        if ($storeEmployeeRole) {
            $employee->assignRole($storeEmployeeRole);
        }

        // Create a cashier
        $cashier = Vendor::create([
            'first_name' => 'فاطمة',
            'last_name' => 'علي',
            'email' => 'cashier@example.com',
            'phone' => '2222222222',
            'password' => Hash::make('password123'),
            'is_owner' => false,
            'is_active' => true,
            'store_id' => 1,
        ]);
        if ($cashierRole) {
            $cashier->assignRole($cashierRole);
        }
    }
}
