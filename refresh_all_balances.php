<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmsService;
use App\Models\VtuService;
use App\Services\SmsProviderService;
use App\Services\VtuNgService;

echo "=== Refreshing All API Service Balances ===\n\n";

// Refresh SMS Provider Balances
echo "SMS Providers:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$smsServices = SmsService::where('is_active', true)->get();
$smsProviderService = app(SmsProviderService::class);

foreach ($smsServices as $service) {
    echo "\n{$service->name} ({$service->provider}):\n";
    echo "  Old Balance: ₦" . number_format($service->balance, 2) . "\n";
    
    try {
        $newBalance = $smsProviderService->getBalance($service);
        $service->update(['balance' => $newBalance]);
        
        echo "  ✅ New Balance: ₦" . number_format($newBalance, 2) . "\n";
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Refresh VTU Provider Balances
echo "VTU Providers:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$vtuServices = VtuService::where('is_active', true)->get();

foreach ($vtuServices as $service) {
    echo "\n{$service->name} ({$service->provider}):\n";
    echo "  Old Balance: ₦" . number_format($service->balance, 2) . "\n";
    
    try {
        if ($service->provider === 'vtu_ng') {
            $vtuProvider = app(VtuNgService::class);
            $result = $vtuProvider->getBalance();
            
            if ($result['success']) {
                $newBalance = (float)$result['balance'];
                $service->update(['balance' => $newBalance]);
                echo "  ✅ New Balance: ₦" . number_format($newBalance, 2) . "\n";
            } else {
                echo "  ❌ Error: " . ($result['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "  ⚠️  Provider '{$service->provider}' not supported yet\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n✅ Balance refresh complete!\n\n";

// Show final summary
echo "Final Balances:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "\nSMS Services:\n";
foreach (SmsService::where('is_active', true)->get() as $s) {
    echo "  {$s->name}: ₦" . number_format($s->balance, 2) . "\n";
}

echo "\nVTU Services:\n";
foreach (VtuService::where('is_active', true)->get() as $v) {
    echo "  {$v->name}: ₦" . number_format($v->balance, 2) . "\n";
}

echo "\n=== Complete ===\n";

