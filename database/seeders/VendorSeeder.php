<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = DB::table('stores')->get();

        foreach ($stores as $store) {
            DB::table('vendors')->insert([
                'first_name' => 'مدير',
                'last_name' => $store->id == 1 ? 'ماكدونالدز' : 'بيتزا هت',
                'email' => 'vendor' . $store->id . '@example.com',
                'phone' => '123456789' . $store->id,
                'password' => Hash::make('password123'),
                'is_owner' => true,
                'is_active' => true,
                'store_id' => $store->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
