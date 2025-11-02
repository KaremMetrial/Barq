<?php

namespace Database\Seeders;

use Modules\Setting\Models\Setting;
use App\Enums\SettingTypeEnum;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'support_contact_number',
                'value' => '+20123456789',
                'type' => SettingTypeEnum::STRING,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
