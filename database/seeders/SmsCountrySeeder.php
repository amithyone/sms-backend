<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsCountrySeeder extends Seeder
{
    public function run(): void
    {
        $provider = 'tiger_sms';
        $pairs = [
            ['id' => '74', 'name' => 'Afghanistan'],
            ['id' => '155', 'name' => 'Albania'],
            ['id' => '58', 'name' => 'Algeria'],
            ['id' => '76', 'name' => 'Angola'],
            ['id' => '181', 'name' => 'Anguilla'],
            ['id' => '169', 'name' => 'Antigua and Barbuda'],
            ['id' => '39', 'name' => 'Argentinas'],
            ['id' => '148', 'name' => 'Armenia'],
            ['id' => '179', 'name' => 'Aruba'],
            ['id' => '175', 'name' => 'Australia'],
            ['id' => '50', 'name' => 'Austria'],
            ['id' => '35', 'name' => 'Azerbaijan'],
            ['id' => '122', 'name' => 'Bahamas'],
            ['id' => '145', 'name' => 'Bahrain'],
            ['id' => '60', 'name' => 'Bangladesh'],
            ['id' => '118', 'name' => 'Barbados'],
            ['id' => '51', 'name' => 'Belarus'],
            ['id' => '82', 'name' => 'Belgium'],
            ['id' => '124', 'name' => 'Belize'],
            ['id' => '120', 'name' => 'Benin'],
            ['id' => '195', 'name' => 'Bermuda'],
            ['id' => '158', 'name' => 'Bhutan'],
            ['id' => '92', 'name' => 'Bolivia'],
            ['id' => '108', 'name' => 'Bosnia and Herzegovina'],
            ['id' => '123', 'name' => 'Botswana'],
            ['id' => '73', 'name' => 'Brazil'],
            ['id' => '121', 'name' => 'Brunei Darussalam'],
            ['id' => '83', 'name' => 'Bulgaria'],
            ['id' => '152', 'name' => 'Burkina Faso'],
            ['id' => '119', 'name' => 'Burundi'],
            ['id' => '24', 'name' => 'Cambodia'],
            ['id' => '41', 'name' => 'Cameroon'],
            ['id' => '36', 'name' => 'Canada'],
            // ... continue adding as needed ...
            ['id' => '19', 'name' => 'Nigeria'],
            ['id' => '16', 'name' => 'United Kingdom'],
            ['id' => '187', 'name' => 'United States'],
            ['id' => '31', 'name' => 'South Africa'],
            ['id' => '38', 'name' => 'Ghana'],
            ['id' => '8', 'name' => 'Kenya'],
            ['id' => '21', 'name' => 'Egypt'],
            ['id' => '4', 'name' => 'Philippines'],
            ['id' => '6', 'name' => 'Indonesia'],
            ['id' => '22', 'name' => 'India'],
        ];

        foreach ($pairs as $row) {
            DB::table('sms_countries')->updateOrInsert(
                ['provider' => $provider, 'country_id' => $row['id']],
                ['name' => $row['name'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}


