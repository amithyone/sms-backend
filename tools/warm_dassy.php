<?php
// Warm Dassy services cache by calling the provider through Laravel services

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

/** @var ConsoleKernel $kernel */
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

/** @var \App\Models\SmsService|null $smsService */
$smsService = \App\Models\SmsService::where('provider', 'dassy')->first();
if (!$smsService) {
    fwrite(STDERR, "Dassy SmsService not found in DB.\n");
    exit(1);
}

$country = 'US';

/** @var \App\Services\SmsProviderService $providerService */
$providerService = app(\App\Services\SmsProviderService::class);
$rows = $providerService->getServices($smsService, $country);

$count = is_array($rows) ? count($rows) : 0;
echo "Fetched {$count} Dassy services for {$country}.\n";

if ($count > 0) {
    /** @var \App\Services\Sms\PriceCacheService $priceCache */
    $priceCache = app(\App\Services\Sms\PriceCacheService::class);
    $priceCache->upsertPrices('dassy', $country, $rows);
    echo "Upserted {$count} rows into sms_service_country_prices.\n";
}

echo "Done.\n";

