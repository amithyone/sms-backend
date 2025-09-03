<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VtuServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vtuServices = [
            [
                'name' => 'VTU.ng Premium',
                'provider' => 'vtu_ng',
                'api_key' => null,
                'username' => env('VTU_NG_USERNAME', 'test_vtu_ng_username'),
                'password' => env('VTU_NG_PASSWORD', 'test_vtu_ng_password'),
                'pin' => env('VTU_NG_PIN', 'test_vtu_ng_pin'),
                'api_url' => env('VTU_NG_BASE_URL', 'https://vtu.ng/wp-json'),
                'is_active' => true,
                'balance' => 0.00,
                'priority' => 1, // First priority - try VTU.ng first
                'success_rate' => 0.00,
                'total_orders' => 0,
                'successful_orders' => 0,
                'settings' => json_encode([
                    'timeout' => 30,
                    'retry_attempts' => 3,
                    'auto_cancel' => true,
                    'token_cache_minutes' => env('VTU_NG_TOKEN_CACHE_MINUTES', 10080)
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'iRecharge VTU',
                'provider' => 'irecharge',
                'api_key' => null,
                'username' => env('IRECHARGE_USERNAME', 'test_irecharge_username'),
                'password' => env('IRECHARGE_PASSWORD', 'test_irecharge_password'),
                'pin' => null,
                'api_url' => env('IRECHARGE_BASE_URL', 'https://irecharge.com.ng/pwr_api_sandbox/'),
                'is_active' => true,
                'balance' => 0.00,
                'priority' => 2, // Second priority - fallback to iRecharge
                'success_rate' => 0.00,
                'total_orders' => 0,
                'successful_orders' => 0,
                'settings' => json_encode([
                    'timeout' => 25,
                    'retry_attempts' => 2,
                    'auto_cancel' => true
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($vtuServices as $service) {
            DB::table('vtu_services')->insert($service);
        }

        $this->command->info('VTU services seeded successfully!');
    }
}
