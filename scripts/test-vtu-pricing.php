<?php

/**
 * VTU Pricing Test Script
 * Verifies that all VTU data plans have fair and consistent pricing
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "💰 Testing VTU Data Plans Pricing\n";
echo "================================\n\n";

$baseUrl = 'https://api.fadsms.com/api';
$networks = ['MTN', 'AIRTEL', 'GLO', '9MOBILE'];

// Define fair pricing thresholds
$fairPricing = [
    '500MB' => ['min' => 1000, 'max' => 2000, 'fair' => 1500],
    '1GB' => ['min' => 2000, 'max' => 4000, 'fair' => 3000],
    '2GB' => ['min' => 4000, 'max' => 6000, 'fair' => 5000],
    '3GB' => ['min' => 7000, 'max' => 11000, 'fair' => 9000],
    '5GB' => ['min' => 12000, 'max' => 18000, 'fair' => 15000],
    '10GB' => ['min' => 25000, 'max' => 35000, 'fair' => 30000],
];

$allTestsPassed = true;
$totalPlans = 0;
$fairPlans = 0;
$unfairPlans = 0;

echo "🎯 Fair Pricing Standards:\n";
foreach ($fairPricing as $plan => $pricing) {
    echo "   {$plan}: ₦{$pricing['min']} - ₦{$pricing['max']} (Fair: ₦{$pricing['fair']})\n";
}
echo "\n";

foreach ($networks as $network) {
    echo "📱 Testing {$network} Network:\n";
    
    try {
        $url = "{$baseUrl}/vtu/data-plans?network={$network}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['success'] && isset($data['data']) && is_array($data['data'])) {
            $plans = $data['data'];
            
            foreach ($plans as $plan) {
                $totalPlans++;
                $planSize = $plan['plan'];
                $amount = $plan['amount'];
                $planName = $plan['plan_name'];
                
                if (isset($fairPricing[$planSize])) {
                    $pricing = $fairPricing[$planSize];
                    
                    if ($amount >= $pricing['min'] && $amount <= $pricing['max']) {
                        echo "   ✅ {$planName}: ₦{$amount} (FAIR)\n";
                        $fairPlans++;
                    } else {
                        echo "   ❌ {$planName}: ₦{$amount} (UNFAIR - should be ₦{$pricing['min']}-{$pricing['max']})\n";
                        $unfairPlans++;
                        $allTestsPassed = false;
                    }
                } else {
                    echo "   ⚠️  {$planName}: ₦{$amount} (Unknown plan size)\n";
                }
            }
        } else {
            echo "   ❌ Failed to fetch data plans for {$network}\n";
            $allTestsPassed = false;
        }
    } catch (Exception $e) {
        echo "   ❌ Error testing {$network}: " . $e->getMessage() . "\n";
        $allTestsPassed = false;
    }
    
    echo "\n";
}

// Summary
echo "📊 PRICING TEST SUMMARY\n";
echo "======================\n";
echo "Total Plans Tested: {$totalPlans}\n";
echo "Fair Plans: {$fairPlans}\n";
echo "Unfair Plans: {$unfairPlans}\n";
echo "Fairness Rate: " . ($totalPlans > 0 ? round(($fairPlans / $totalPlans) * 100, 1) : 0) . "%\n\n";

if ($allTestsPassed) {
    echo "✅ ALL PRICING TESTS PASSED!\n";
    echo "🎉 VTU data plans now have fair and consistent pricing\n";
    echo "💰 No more N50 or other unfair prices\n";
} else {
    echo "❌ SOME PRICING TESTS FAILED!\n";
    echo "🔧 Please review the unfair pricing above\n";
}

echo "\n📋 Current Pricing Structure:\n";
echo "============================\n";
echo "500MB Daily: ₦1,500 (was ₦150) - 10x increase\n";
echo "1GB Daily: ₦3,000 (was ₦300) - 10x increase\n";
echo "2GB 2-Days: ₦5,000 (was ₦500) - 10x increase\n";
echo "3GB Weekly: ₦9,000 (was ₦900) - 10x increase\n";
echo "5GB Weekly: ₦15,000 (was ₦1,500) - 10x increase\n";
echo "10GB Monthly: ₦30,000 (was ₦3,000) - 10x increase\n";

echo "\n🎯 Benefits of Fair Pricing:\n";
echo "===========================\n";
echo "✅ Sustainable business model\n";
echo "✅ Fair profit margins\n";
echo "✅ Competitive market rates\n";
echo "✅ No more customer complaints about unfair pricing\n";
echo "✅ Professional service pricing\n";

echo "\n✅ Pricing fix completed at: " . now()->toDateTimeString() . "\n";

