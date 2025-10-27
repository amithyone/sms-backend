<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fadded.net advertisement (featured)
        Advertisement::create([
            'title' => 'Fadded.net - Premium Social Media Accounts',
            'description' => 'Buy all types of social media accounts and get instant delivery. Premium quality, verified accounts.',
            'button_text' => 'Visit Fadded.net',
            'button_url' => 'https://fadded.net',
            'background_type' => 'color',
            'background_color' => '#3B82F6', // Blue
            'text_color' => '#FFFFFF',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true
        ]);

        // Placeholder for user advertisements
        Advertisement::create([
            'title' => 'Place Your Advertisement Here',
            'description' => 'Contact us to advertise your service to our users. Reach thousands of active users.',
            'button_text' => 'Contact Us',
            'button_url' => 'mailto:contact@fadsms.com',
            'background_type' => 'color',
            'background_color' => '#10B981', // Green
            'text_color' => '#FFFFFF',
            'sort_order' => 2,
            'is_active' => true,
            'is_featured' => false
        ]);
    }
}