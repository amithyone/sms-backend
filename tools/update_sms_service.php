<?php

use App\Models\SmsService;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Update or create Dassy (DaisySMS) provider credentials from .env
$apiUrl = env('DASSY_BASE_URL', 'https://daisysms.com/stubs/handler_api.php');
$apiKey = env('DASSY_API_KEY', '');

if (empty($apiKey)) {
    echo "DASSY_API_KEY is empty in .env\n";
    exit(1);
}

$svc = SmsService::query()->where('provider', SmsService::PROVIDER_DASSY)->first();
if (!$svc) {
    $svc = new SmsService();
    $svc->name = 'DaisySMS';
    $svc->provider = SmsService::PROVIDER_DASSY;
    $svc->priority = 20;
}
$svc->api_url = $apiUrl;
$svc->api_key = $apiKey;
$svc->is_active = true;
$svc->save();

echo "Updated Dassy provider id={$svc->id}, url={$svc->api_url}\n";

// Update or create TextVerified provider
$tvKey = env('TEXTVERIFIED_API_KEY', '');
$tvUser = env('TEXTVERIFIED_API_USERNAME', '');
if (!empty($tvKey) && !empty($tvUser)) {
    $tv = SmsService::query()->where('provider', SmsService::PROVIDER_TEXTVERIFIED)->first();
    if (!$tv) {
        $tv = new SmsService();
        $tv->name = 'TextVerified';
        $tv->provider = SmsService::PROVIDER_TEXTVERIFIED;
        $tv->priority = 30;
    }
    $tv->api_url = 'https://www.textverified.com/api/pub/v2';
    $tv->api_key = $tvKey;
    $settings = $tv->settings ?? [];
    $settings['username'] = $tvUser;
    $tv->settings = $settings;
    $tv->is_active = true;
    $tv->save();
    echo "Updated TextVerified provider id={$tv->id}\n";
} else {
    echo "TEXTVERIFIED_API_KEY or TEXTVERIFIED_API_USERNAME missing in .env\n";
}


