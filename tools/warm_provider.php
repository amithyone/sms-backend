<?php
// Warm cache for a specific provider and country via Laravel services

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

if (php_sapi_name() !== 'cli') { exit(1); }

$provider = isset($argv[1]) ? strtolower($argv[1]) : 'dassy';
$country = isset($argv[2]) ? strtoupper($argv[2]) : 'US';

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

/** @var ConsoleKernel $kernel */
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

/** @var \App\Models\SmsService|null $smsService */
$smsService = \App\Models\SmsService::where('provider', $provider)->first();
if (!$smsService) {
    fwrite(STDERR, "SmsService not found for provider: {$provider}\n");
    exit(2);
}

/** @var \App\Services\SmsProviderService $providerService */
$providerService = app(\App\Services\SmsProviderService::class);
$rows = $providerService->getServices($smsService, $country);

$count = is_array($rows) ? count($rows) : 0;
echo "Fetched {$count} {$provider} services for {$country}.\n";

if ($count > 0) {
    /** @var \App\Services\Sms\PriceCacheService $priceCache */
    $priceCache = app(\App\Services\Sms\PriceCacheService::class);
    $priceCache->upsertPrices($provider, $country, $rows);
    echo "Upserted {$count} rows into sms_service_country_prices for {$provider}/{$country}.\n";
}

echo "Done.\n";

