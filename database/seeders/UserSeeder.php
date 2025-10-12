<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed@example.com',
                'phone' => '201006567831',
                'status' => 'active',
                'balance' => 150.000,
            ],
            [
                'first_name' => 'فاطمة',
                'last_name' => 'علي',
                'email' => 'fatima@example.com',
                'phone' => '201006567832',
                'status' => 'active',
                'balance' => 75.000,
            ],
            [
                'first_name' => 'خالد',
                'last_name' => 'إبراهيم',
                'email' => 'khaled@example.com',
                'phone' => '201006567823',
                'status' => 'active',
                'balance' => 200.000,
            ]
        ];

        $zoneId = DB::table('zones')->first()->id;

        foreach ($users as $user) {
            $userId = DB::table('users')->insertGetId([
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'status' => $user['status'],
                'balance' => $user['balance'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // User addresses
            DB::table('addresses')->insert([
                [
                    'latitude' => 30.0444 + (rand(0, 100) / 10000),
                    'longitude' => 31.2357 + (rand(0, 100) / 10000),
                    'name' => 'المنزل',
                    'phone' => $user['phone'],
                    'is_default' => true,
                    'type' => 'home',
                    'addressable_type' => 'App\\Models\\User',
                    'addressable_id' => $userId,
                    'zone_id' => $zoneId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'latitude' => 30.0444 + (rand(0, 100) / 10000),
                    'longitude' => 31.2357 + (rand(0, 100) / 10000),
                    'name' => 'العمل',
                    'phone' => $user['phone'],
                    'is_default' => false,
                    'type' => 'work',
                    'addressable_type' => 'App\\Models\\User',
                    'addressable_id' => $userId,
                    'zone_id' => $zoneId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
