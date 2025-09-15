<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsService;

class SmsServiceSeeder extends Seeder
{
    public function run(): void
    {
        $smsServices = [
            [
                'name' => '5Sim Premium',
                'provider' => SmsService::PROVIDER_5SIM,
                'api_key' => env('5SIM_API_KEY', 'your_5sim_api_key_here'),
                'api_url' => config('services.sms.5sim.base_url'),
                'is_active' => true,
                'priority' => 1,
                'settings' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                    'auto_cancel' => true
                ]
            ],
            [
                'name' => 'Dassy SMS',
                'provider' => SmsService::PROVIDER_DASSY,
                'api_key' => env('DASSY_API_KEY', 'your_dassy_api_key_here'),
                'api_url' => config('services.sms.dassy.base_url'),
                'is_active' => true,
                'priority' => 2,
                'settings' => [
                    'timeout' => 25,
                    'retry_attempts' => 2,
                    'auto_cancel' => true
                ]
            ],
            [
                'name' => 'Tiger SMS',
                'provider' => SmsService::PROVIDER_TIGER_SMS,
                'api_key' => env('TIGER_SMS_API_KEY', 'your_tiger_sms_api_key_here'),
                'api_url' => config('services.sms.tiger_sms.base_url'),
                'is_active' => true,
                'priority' => 3,
                'settings' => [
                    'timeout' => 35,
                    'retry_attempts' => 3,
                    'auto_cancel' => true
                ]
            ],
            [
                'name' => 'TextVerified',
                'provider' => SmsService::PROVIDER_TEXTVERIFIED,
                'api_key' => env('TEXTVERIFIED_API_KEY', 'your_textverified_api_key_here'),
                'api_url' => 'https://www.textverified.com/api/v2',
                'is_active' => true,
                'priority' => 4,
                'settings' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                    'auto_cancel' => true,
                    'username' => env('TEXTVERIFIED_API_USERNAME', 'your_textverified_username_here')
                ]
            ]
        ];

        foreach ($smsServices as $service) {
            SmsService::updateOrCreate(
                ['provider' => $service['provider']],
                $service
            );
        }

        $this->command->info('SMS Services seeded successfully!');
        $this->command->info('Please update your .env file with the actual API keys:');
        $this->command->info('- 5SIM_API_KEY');
        $this->command->info('- DASSY_API_KEY');
        $this->command->info('- TIGER_SMS_API_KEY');
        $this->command->info('- TEXTVERIFIED_API_KEY');
    }
}
