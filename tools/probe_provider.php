<?php

use App\Models\SmsService;
use App\Services\SmsProviderService;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$providerKey = $argv[1] ?? null; // 5sim|dassy|tiger_sms|textverified
$country = $argv[2] ?? null;
$service = $argv[3] ?? null;

if (!$providerKey) {
    echo "Usage: php tools/probe_provider.php <provider> [country] [service]\n";
    exit(1);
}

$svc = SmsService::query()->where('provider', $providerKey)->first();
if (!$svc) {
    echo "Provider not found: {$providerKey}\n";
    exit(1);
}

$manager = new SmsProviderService();
$samples = $manager->debugProbe($svc, $country, $service);
echo json_encode($samples, JSON_PRETTY_PRINT) . "\n";


