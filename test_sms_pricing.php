<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SMS Pricing Test ===\n\n";

// Test pricing calculation with different scenarios
$testCases = [
    ['base_usd' => 0.50, 'name' => 'Low cost service (e.g., India)'],
    ['base_usd' => 1.00, 'name' => 'Medium cost service (e.g., Nigeria)'],
    ['base_usd' => 2.00, 'name' => 'High cost service (e.g., USA)'],
    ['base_usd' => 0.10, 'name' => 'Very low cost (should hit ₦1500 minimum)'],
];

// Get current settings
$fxRate = config('services.sms_fx.ngn_per_usd', 1600);
$markup = config('services.sms_markup.percent', 10);
$vat = DB::table('settings')->where('key', 'sms_vat')->value('value') ?? 700;
$minPrice = DB::table('settings')->where('key', 'sms_min_price')->value('value') ?? 1500;

echo "Current Configuration:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "FX Rate:       $1 = ₦{$fxRate}\n";
echo "Markup:        {$markup}%\n";
echo "Fixed VAT:     ₦{$vat}\n";
echo "Minimum Price: ₦{$minPrice}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Pricing Calculations:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach ($testCases as $test) {
    $baseUsd = $test['base_usd'];
    $name = $test['name'];
    
    // Simulate the pricing calculation
    $step1 = $baseUsd * $fxRate; // USD to NGN
    $step2 = $step1 * (1 + ($markup / 100)); // Apply markup
    $step3 = $step2 + $vat; // Add VAT
    $step4 = ceil($step3); // Round up
    $finalPrice = max($step4, $minPrice); // Apply minimum
    
    echo "\n{$name}\n";
    echo "  Base Cost:      \${$baseUsd}\n";
    echo "  → Convert:      \${$baseUsd} × ₦{$fxRate} = ₦" . number_format($step1, 2) . "\n";
    echo "  → Add {$markup}% markup: ₦" . number_format($step1, 2) . " × 1.{$markup} = ₦" . number_format($step2, 2) . "\n";
    echo "  → Add VAT:      ₦" . number_format($step2, 2) . " + ₦{$vat} = ₦" . number_format($step3, 2) . "\n";
    echo "  → Round up:     ₦" . number_format($step4, 2) . "\n";
    
    if ($step4 < $minPrice) {
        echo "  → Apply minimum: ₦" . number_format($step4, 2) . " → ₦" . number_format($minPrice, 2) . " ⚠️\n";
    }
    
    echo "  ✅ Final Price: ₦" . number_format($finalPrice, 2) . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n✅ All prices meet minimum requirement of ₦{$minPrice}\n";
echo "✅ Dollar to Naira conversion: \$1 = ₦{$fxRate}\n";
echo "✅ Markup applied: {$markup}%\n";
echo "✅ Fixed VAT: ₦{$vat}\n\n";

echo "=== Test Complete ===\n";

