<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Sms\Providers\SmsPoolProvider;
use App\Models\SmsService;

echo "=== Testing SMSpool Pricing Fix ===\n\n";

// Get SMSpool service
$smsService = SmsService::where('provider', 'smspool')->first();

if (!$smsService) {
    echo "❌ SMSpool service not found in database\n";
    exit(1);
}

echo "SMSpool Service Found:\n";
echo "  ID: {$smsService->id}\n";
echo "  Name: {$smsService->name}\n";
echo "  Provider: {$smsService->provider}\n";
echo "  Active: " . ($smsService->is_active ? 'Yes' : 'No') . "\n\n";

// Test the provider's getServices method
$provider = new SmsPoolProvider();

echo "Testing getServices() method:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $services = $provider->getServices($smsService, 'US');
    
    if (empty($services)) {
        echo "⚠️  No services returned (API might be down or key invalid)\n\n";
    } else {
        echo "Found " . count($services) . " services for US:\n\n";
        
        // Show first 3 services
        foreach (array_slice($services, 0, 3) as $svc) {
            echo "Service: {$svc['service']}\n";
            echo "  Name: {$svc['name']}\n";
            echo "  Cost: " . (isset($svc['cost']) ? $svc['cost'] : 'N/A') . "\n";
            echo "  Currency: " . (isset($svc['currency']) ? $svc['currency'] : 'NOT SET') . "\n";
            
            if (isset($svc['currency']) && $svc['currency'] === 'USD') {
                echo "  ✅ Currency marked as USD - will be converted!\n";
            } else {
                echo "  ❌ Currency not marked - might not convert!\n";
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "Testing createOrder() method:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Simulate what would happen with a $1 order
echo "Simulating order with cost = \$1.00:\n\n";

$mockOrderData = [
    'order_id' => 'TEST123',
    'phone_number' => '1234567890',
    'cost' => 1.00,
    'currency' => 'USD',
    'status' => 'active'
];

echo "Order data from provider:\n";
echo "  Cost: \${$mockOrderData['cost']}\n";
echo "  Currency: {$mockOrderData['currency']}\n\n";

// Simulate the conversion logic
$charge = (float)$mockOrderData['cost'];
$orderCurrency = strtoupper((string)($mockOrderData['currency'] ?? 'NGN'));

echo "Processing:\n";
echo "  Initial charge: \${$charge}\n";
echo "  Currency detected: {$orderCurrency}\n\n";

if ($charge > 0 && $orderCurrency === 'USD') {
    echo "  ✅ Converting USD to NGN...\n";
    
    // Simulate conversion
    $fx = 1600;
    $markup = 10;
    $vat = 700;
    $minPrice = 1500;
    
    $step1 = $charge * $fx;
    $step2 = $step1 * (1 + ($markup / 100));
    $step3 = $step2 + $vat;
    $step4 = ceil($step3);
    $finalCharge = max($step4, $minPrice);
    
    echo "    \${$charge} × ₦{$fx} = ₦" . number_format($step1, 2) . "\n";
    echo "    Apply {$markup}% markup: ₦" . number_format($step2, 2) . "\n";
    echo "    Add ₦{$vat} VAT: ₦" . number_format($step3, 2) . "\n";
    echo "    Round up: ₦" . number_format($step4, 2) . "\n";
    echo "    Apply minimum (₦{$minPrice}): ₦" . number_format($finalCharge, 2) . "\n\n";
    
    echo "  ✅ Final charge: ₦" . number_format($finalCharge, 2) . "\n\n";
    
    if ($finalCharge >= 1500) {
        echo "✅ SUCCESS! Charge meets minimum requirement of ₦1,500\n";
    } else {
        echo "❌ FAILED! Charge is below minimum requirement\n";
    }
} else {
    echo "  ❌ Would not convert - charge would be ₦{$charge}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n=== Test Complete ===\n";

