<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin Account
        User::updateOrCreate(
            ['email' => 'admin@leezofood.com'],
            [
                'name' => 'Admin Leezofood',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Essential Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'Leezofood Logistics'],
            ['key' => 'contact_email', 'value' => 'leezointegratedserviceslimited@gmail.com'],
            ['key' => 'contact_phone', 'value' => '+234 813 671 0716, +234 703 989 0112'],
            ['key' => 'default_shipping_rate', 'value' => '5000'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
