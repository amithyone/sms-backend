<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsCountryCuratedSeeder extends Seeder
{
    public function run(): void
    {
        $providers = ['tiger_sms', '5sim'];

        $rows = [
            // North America
            ['id' => '187', 'name' => 'United States'],
            ['id' => '1001', 'name' => 'United States VIP'],
            ['id' => '12', 'name' => 'United States virt'],
            ['id' => '36', 'name' => 'Canada'],
            ['id' => '54', 'name' => 'Mexico'],
            // UK
            ['id' => '16', 'name' => 'United Kingdom'],
            // Top Europe
            ['id' => '43', 'name' => 'Germany'],
            ['id' => '78', 'name' => 'France'],
            ['id' => '86', 'name' => 'Italy'],
            ['id' => '56', 'name' => 'Spain'],
            ['id' => '48', 'name' => 'Netherlands'],
            ['id' => '173', 'name' => 'Switzerland'],
            ['id' => '46', 'name' => 'Sweden'],
            ['id' => '15', 'name' => 'Poland'],
            // Australia
            ['id' => '175', 'name' => 'Australia'],
            // Key Middle East
            ['id' => '53', 'name' => 'Saudi Arabia'],
            ['id' => '95', 'name' => 'United Arab Emirates'],
            ['id' => '111', 'name' => 'Qatar'],
            ['id' => '100', 'name' => 'Kuwait'],
            ['id' => '145', 'name' => 'Bahrain'],
            ['id' => '13', 'name' => 'Israel'],
            ['id' => '62', 'name' => 'Turkey'],
            // Top Africa
            ['id' => '19', 'name' => 'Nigeria'],
            ['id' => '31', 'name' => 'South Africa'],
            ['id' => '21', 'name' => 'Egypt'],
            ['id' => '37', 'name' => 'Morocco'],
            ['id' => '8', 'name' => 'Kenya'],
            ['id' => '38', 'name' => 'Ghana'],
            // Top Asia
            ['id' => '22', 'name' => 'India'],
            ['id' => '3', 'name' => 'China'],
            ['id' => '6', 'name' => 'Indonesia'],
            ['id' => '4', 'name' => 'Philippines'],
            ['id' => '10', 'name' => 'Vietnam'],
            ['id' => '182', 'name' => 'Japan'],
            ['id' => '190', 'name' => 'Korea'],
            ['id' => '52', 'name' => 'Thailand'],
            ['id' => '7', 'name' => 'Malaysia'],
            ['id' => '60', 'name' => 'Bangladesh'],
            ['id' => '66', 'name' => 'Pakistan'],
        ];

        foreach ($providers as $provider) {
            $keep = [];
            foreach ($rows as $row) {
                DB::table('sms_countries')->updateOrInsert(
                    ['provider' => $provider, 'country_id' => (string)$row['id']],
                    ['name' => $row['name'], 'updated_at' => now(), 'created_at' => now()]
                );
                $keep[] = (string)$row['id'];
            }
            DB::table('sms_countries')
                ->where('provider', $provider)
                ->whereNotIn('country_id', $keep)
                ->delete();
        }
    }
}


