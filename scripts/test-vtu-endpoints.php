<?php

/**
 * VTU Data Plans API Test Script
 * Tests all VTU endpoints to ensure consistent response format
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing VTU Data Plans API Endpoints\n";
echo "=====================================\n\n";

$baseUrl = 'https://api.fadsms.com/api';
$networks = ['MTN', 'AIRTEL', 'GLO', '9MOBILE'];
$endpoints = [
    '/vtu/data-plans',
    '/vtu/variations/data'
];

$allTestsPassed = true;

// Test 1: Data Networks Endpoint
echo "1. Testing Data Networks Endpoint...\n";
try {
    $response = file_get_contents("{$baseUrl}/vtu/data/networks");
    $data = json_decode($response, true);
    
    if ($data && $data['success'] && isset($data['data']) && is_array($data['data'])) {
        echo "✅ Data networks endpoint working\n";
        echo "   Networks found: " . count($data['data']) . "\n";
        foreach ($data['data'] as $network) {
            echo "   - {$network['name']} ({$network['code']})\n";
        }
    } else {
        echo "❌ Data networks endpoint failed\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "❌ Data networks endpoint error: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 2: Data Plans Endpoints for Each Network
foreach ($endpoints as $endpoint) {
    echo "2. Testing {$endpoint} endpoint...\n";
    
    foreach ($networks as $network) {
        try {
            $url = "{$baseUrl}{$endpoint}?network={$network}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if ($data && $data['success'] && isset($data['data']) && is_array($data['data'])) {
                $planCount = count($data['data']);
                echo "✅ {$network}: {$planCount} plans found\n";
                
                // Validate plan structure
                if ($planCount > 0) {
                    $firstPlan = $data['data'][0];
                    $requiredFields = ['plan', 'plan_name', 'amount', 'network'];
                    $missingFields = [];
                    
                    foreach ($requiredFields as $field) {
                        if (!isset($firstPlan[$field])) {
                            $missingFields[] = $field;
                        }
                    }
                    
                    if (empty($missingFields)) {
                        echo "   ✅ Plan structure valid\n";
                        echo "   📋 Sample plan: {$firstPlan['plan_name']} - ₦{$firstPlan['amount']}\n";
                    } else {
                        echo "   ❌ Missing fields: " . implode(', ', $missingFields) . "\n";
                        $allTestsPassed = false;
                    }
                }
            } else {
                echo "❌ {$network}: Invalid response format\n";
                echo "   Response: " . substr($response, 0, 200) . "...\n";
                $allTestsPassed = false;
            }
        } catch (Exception $e) {
            echo "❌ {$network}: Error - " . $e->getMessage() . "\n";
            $allTestsPassed = false;
        }
    }
    
    echo "\n";
}

// Test 3: Frontend Compatibility Test
echo "3. Testing Frontend Compatibility...\n";
try {
    // Simulate frontend request
    $testNetwork = 'MTN';
    $response = file_get_contents("{$baseUrl}/vtu/data-plans?network={$testNetwork}");
    $data = json_decode($response, true);
    
    if ($data && $data['success'] && isset($data['data']) && is_array($data['data'])) {
        echo "✅ Frontend can parse response\n";
        
        // Test if frontend can iterate over plans
        $plans = $data['data'];
        if (count($plans) > 0) {
            echo "✅ Frontend can iterate over " . count($plans) . " plans\n";
            
            // Test if frontend can access plan properties
            $samplePlan = $plans[0];
            $frontendCanAccess = isset($samplePlan['plan']) && 
                                isset($samplePlan['plan_name']) && 
                                isset($samplePlan['amount']) && 
                                isset($samplePlan['network']);
            
            if ($frontendCanAccess) {
                echo "✅ Frontend can access all plan properties\n";
                echo "   📱 Sample: {$samplePlan['plan_name']} for {$samplePlan['network']} - ₦{$samplePlan['amount']}\n";
            } else {
                echo "❌ Frontend cannot access all plan properties\n";
                $allTestsPassed = false;
            }
        }
    } else {
        echo "❌ Frontend cannot parse response\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "❌ Frontend compatibility test failed: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 4: Error Handling
echo "4. Testing Error Handling...\n";
try {
    // Test with invalid network
    $response = file_get_contents("{$baseUrl}/vtu/data-plans?network=INVALID");
    $data = json_decode($response, true);
    
    if ($data && $data['success'] && isset($data['data']) && is_array($data['data'])) {
        echo "✅ Invalid network handled gracefully (returns fallback data)\n";
    } else {
        echo "⚠️  Invalid network returns error (acceptable)\n";
    }
    
    // Test without network parameter
    $response = file_get_contents("{$baseUrl}/vtu/data-plans");
    $data = json_decode($response, true);
    
    if ($data && !$data['success']) {
        echo "✅ Missing network parameter returns proper error\n";
    } else {
        echo "❌ Missing network parameter should return error\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "❌ Error handling test failed: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 5: Response Format Consistency
echo "5. Testing Response Format Consistency...\n";
$formats = [];
foreach ($networks as $network) {
    try {
        $response = file_get_contents("{$baseUrl}/vtu/data-plans?network={$network}");
        $data = json_decode($response, true);
        
        if ($data) {
            $format = [
                'has_success' => isset($data['success']),
                'has_data' => isset($data['data']),
                'has_message' => isset($data['message']),
                'data_is_array' => is_array($data['data']),
                'data_count' => is_array($data['data']) ? count($data['data']) : 0
            ];
            $formats[$network] = $format;
        }
    } catch (Exception $e) {
        echo "❌ Failed to test {$network}: " . $e->getMessage() . "\n";
        $allTestsPassed = false;
    }
}

// Check if all formats are consistent
$firstFormat = reset($formats);
$allConsistent = true;
foreach ($formats as $network => $format) {
    if ($format !== $firstFormat) {
        echo "❌ {$network} format inconsistent\n";
        $allConsistent = false;
        $allTestsPassed = false;
    }
}

if ($allConsistent) {
    echo "✅ All networks return consistent response format\n";
    echo "   Format: success={$firstFormat['has_success']}, data={$firstFormat['has_data']}, message={$firstFormat['has_message']}\n";
    echo "   Data structure: array with {$firstFormat['data_count']} items\n";
}

echo "\n";

// Summary
echo "📋 VTU DATA PLANS API TEST SUMMARY\n";
echo "==================================\n";

if ($allTestsPassed) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "🎯 VTU data plans API is working correctly\n";
    echo "📱 Frontend should be able to display data plans properly\n";
} else {
    echo "❌ SOME TESTS FAILED!\n";
    echo "🔧 Please review the issues above\n";
}

echo "\n📊 Available Endpoints:\n";
echo "   - GET /api/vtu/data/networks (get available networks)\n";
echo "   - GET /api/vtu/data-plans?network=NETWORK (get data plans)\n";
echo "   - GET /api/vtu/variations/data?network=NETWORK (alternative endpoint)\n";

echo "\n📱 Frontend Integration:\n";
echo "   - Use /api/vtu/data-plans endpoint\n";
echo "   - Pass network parameter (MTN, AIRTEL, GLO, 9MOBILE)\n";
echo "   - Response format: { success: true, data: [plans], message: '...' }\n";
echo "   - Each plan has: plan, plan_name, amount, network\n";

echo "\n✅ Test completed at: " . now()->toDateTimeString() . "\n";
