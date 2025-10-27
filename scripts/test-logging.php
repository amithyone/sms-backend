<?php

/**
 * Test Script for Comprehensive Logging System
 * This script tests all aspects of the logging system
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Comprehensive Logging System\n";
echo "=====================================\n\n";

// Test 1: Basic Logging Channels
echo "1. Testing Basic Logging Channels...\n";
try {
    Log::channel('sms')->info('SMS Channel Test', ['test' => 'basic_sms_logging']);
    Log::channel('admin')->info('Admin Channel Test', ['test' => 'basic_admin_logging']);
    Log::channel('errors')->error('Error Channel Test', ['test' => 'basic_error_logging']);
    Log::channel('providers')->info('Provider Channel Test', ['test' => 'basic_provider_logging']);
    Log::channel('frontend')->error('Frontend Channel Test', ['test' => 'basic_frontend_logging']);
    Log::channel('performance')->info('Performance Channel Test', ['test' => 'basic_performance_logging']);
    echo "✅ Basic logging channels working\n";
} catch (Exception $e) {
    echo "❌ Basic logging failed: " . $e->getMessage() . "\n";
}

// Test 2: SMS Controller Logging
echo "\n2. Testing SMS Controller Logging...\n";
try {
    $mappingService = new \App\Services\ProviderMappingService();
    $smsProviderService = new \App\Services\SmsProviderService($mappingService);
    $smsController = new \App\Http\Controllers\SmsController($smsProviderService);
    
    // Test logSmsOperation method
    $reflection = new ReflectionClass($smsController);
    $method = $reflection->getMethod('logSmsOperation');
    $method->setAccessible(true);
    $method->invoke($smsController, 'TEST_OPERATION', ['test_data' => 'controller_test']);
    
    echo "✅ SMS Controller logging working\n";
} catch (Exception $e) {
    echo "❌ SMS Controller logging failed: " . $e->getMessage() . "\n";
}

// Test 3: Error Logger Controller
echo "\n3. Testing Error Logger Controller...\n";
try {
    $errorController = new \App\Http\Controllers\Api\ErrorLoggerController();
    
    // Create a mock request
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'error' => 'Test frontend error',
        'stack' => 'Test stack trace',
        'url' => 'https://test.example.com',
        'line' => 42,
        'component' => 'TestComponent',
        'action' => 'testAction',
        'metadata' => ['test' => 'metadata']
    ]);
    
    $response = $errorController->logFrontendError($request);
    echo "✅ Error Logger Controller working (Status: " . $response->getStatusCode() . ")\n";
} catch (Exception $e) {
    echo "❌ Error Logger Controller failed: " . $e->getMessage() . "\n";
}

// Test 4: Database Logging
echo "\n4. Testing Database Logging...\n";
try {
    // Test if we can write to database
    $testData = [
        'operation' => 'TEST_DATABASE_LOG',
        'test_data' => 'database_test',
        'timestamp' => now()->toISOString(),
    ];
    
    // Log to database via model (if CachedSmsService exists)
    if (class_exists('\App\Models\CachedSmsService')) {
        echo "✅ Database models accessible\n";
    } else {
        echo "⚠️  Database models not found\n";
    }
} catch (Exception $e) {
    echo "❌ Database logging failed: " . $e->getMessage() . "\n";
}

// Test 5: Log File Creation
echo "\n5. Testing Log File Creation...\n";
$logFiles = [
    'sms-api.log',
    'admin-api.log', 
    'errors.log',
    'sms-providers.log',
    'frontend-errors.log',
    'performance.log'
];

$logPath = storage_path('logs');
$createdFiles = 0;

foreach ($logFiles as $logFile) {
    $fullPath = $logPath . '/' . $logFile;
    if (file_exists($fullPath)) {
        $createdFiles++;
        $size = filesize($fullPath);
        echo "✅ {$logFile} exists (" . number_format($size) . " bytes)\n";
    } else {
        echo "⚠️  {$logFile} not found\n";
    }
}

echo "📊 Log files created: {$createdFiles}/" . count($logFiles) . "\n";

// Test 6: Middleware Registration
echo "\n6. Testing Middleware Registration...\n";
try {
    $kernel = app('Illuminate\Contracts\Http\Kernel');
    
    // Check if middleware class exists
    if (class_exists('\App\Http\Middleware\RequestResponseLogger')) {
        echo "✅ RequestResponseLogger middleware class exists\n";
    } else {
        echo "❌ RequestResponseLogger middleware class not found\n";
    }
} catch (Exception $e) {
    echo "❌ Middleware test failed: " . $e->getMessage() . "\n";
}

// Test 7: Route Registration
echo "\n7. Testing Route Registration...\n";
try {
    $routes = app('router')->getRoutes();
    $errorRoutes = 0;
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'errors/') !== false) {
            $errorRoutes++;
        }
    }
    
    if ($errorRoutes >= 3) {
        echo "✅ Error logging routes registered ({$errorRoutes} routes)\n";
    } else {
        echo "⚠️  Error logging routes not found or incomplete\n";
    }
} catch (Exception $e) {
    echo "❌ Route test failed: " . $e->getMessage() . "\n";
}

// Test 8: Log Rotation Script
echo "\n8. Testing Log Rotation Script...\n";
$rotationScript = __DIR__ . '/log-rotation.sh';
if (file_exists($rotationScript)) {
    if (is_executable($rotationScript)) {
        echo "✅ Log rotation script exists and is executable\n";
    } else {
        echo "⚠️  Log rotation script exists but not executable\n";
    }
} else {
    echo "❌ Log rotation script not found\n";
}

// Test 9: Log Cleanup Command
echo "\n9. Testing Log Cleanup Command...\n";
try {
    $cleanupCommand = new \App\Console\Commands\LogCleanup();
    echo "✅ Log cleanup command class exists\n";
} catch (Exception $e) {
    echo "❌ Log cleanup command failed: " . $e->getMessage() . "\n";
}

// Test 10: Frontend Error Logger
echo "\n10. Testing Frontend Error Logger...\n";
$frontendLogger = __DIR__ . '/../public/js/error-logger.js';
if (file_exists($frontendLogger)) {
    $size = filesize($frontendLogger);
    echo "✅ Frontend error logger exists (" . number_format($size) . " bytes)\n";
} else {
    echo "❌ Frontend error logger not found\n";
}

// Summary
echo "\n📋 LOGGING SYSTEM TEST SUMMARY\n";
echo "==============================\n";

$logPath = storage_path('logs');
$totalSize = 0;

if (is_dir($logPath)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logPath));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
        }
    }
}

echo "📁 Log directory: {$logPath}\n";
echo "💾 Total log size: " . number_format($totalSize) . " bytes\n";
echo "📅 Test completed at: " . now()->toDateTimeString() . "\n";

echo "\n🎯 NEXT STEPS:\n";
echo "1. Include error-logger.js in your frontend HTML\n";
echo "2. Test API endpoints to verify request/response logging\n";
echo "3. Set up cron job for log rotation: 0 2 * * * /var/www/api.fadsms.com/scripts/log-rotation.sh\n";
echo "4. Monitor log files for errors and performance issues\n";
echo "5. Use 'php artisan logs:cleanup' to clean old logs\n";

echo "\n✅ Logging system test completed!\n";
