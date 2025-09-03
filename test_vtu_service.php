<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing VTU Service ===\n\n";

try {
    // Test DatabaseVtuService
    echo "1. Testing DatabaseVtuService...\n";
    $vtuService = new \App\Services\DatabaseVtuService();
    echo "✓ DatabaseVtuService created successfully\n";
    
    // Test getDataBundles method
    echo "\n2. Testing getDataBundles method...\n";
    $bundles = $vtuService->getDataBundles('mtn');
    echo "✓ getDataBundles method executed successfully\n";
    echo "Result: " . json_encode($bundles, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n✅ All tests passed! VTU service is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
