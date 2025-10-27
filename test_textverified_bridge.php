<?php

/**
 * Test TextVerified using Python client as a bridge
 */

function testTextVerifiedViaPython() {
    $pythonScript = '/var/www/api.fadsms.com/test_textverified_python.py';
    
    // Run the Python script and capture output
    $output = shell_exec("python3 $pythonScript 2>&1");
    
    echo "Python Client Output:\n";
    echo $output . "\n";
    
    // Check if it was successful
    if (strpos($output, '✅ TextVerified Python client is working correctly!') !== false) {
        echo "✅ TextVerified is working via Python client\n";
        return true;
    } else {
        echo "❌ TextVerified failed via Python client\n";
        return false;
    }
}

function getTextVerifiedServicesViaPython() {
    $pythonScript = '/var/www/api.fadsms.com/get_services_python.py';
    
    // Create a script to get services
    $scriptContent = '#!/usr/bin/env python3
from textverified import TextVerified
from textverified import NumberType, ReservationType
import json

client = TextVerified(
    api_key="AOlwncA1BJhofCVZ6sUEYIJ1wgst9tyd1zsodaerhVin4Ev1LxqxQPlFXHeLDm",
    api_username="faddedog@gmail.com",
)

try:
    services = client.services.list(
        number_type=NumberType.MOBILE,
        reservation_type=ReservationType.VERIFICATION
    )
    
    # Return first 10 services as JSON
    result = []
    for service in services[:10]:
        result.append({
            "service": service.service_name,
            "name": service.service_name,
            "cost": 0.0,  # Cost not available in this endpoint
            "count": 1,
            "provider": "textverified",
            "provider_name": "TextVerified"
        })
    
    print(json.dumps(result))
except Exception as e:
    print(json.dumps([]))
';
    
    file_put_contents($pythonScript, $scriptContent);
    chmod($pythonScript, 0755);
    
    $output = shell_exec("python3 $pythonScript 2>&1");
    $services = json_decode($output, true);
    
    if (is_array($services)) {
        echo "✅ Retrieved " . count($services) . " services via Python client\n";
        return $services;
    } else {
        echo "❌ Failed to retrieve services via Python client\n";
        return [];
    }
}

echo "=== TextVerified Bridge Test ===\n\n";

// Test 1: Basic connectivity
echo "1. Testing TextVerified connectivity...\n";
$connected = testTextVerifiedViaPython();
echo "\n";

if ($connected) {
    // Test 2: Get services
    echo "2. Getting services via Python client...\n";
    $services = getTextVerifiedServicesViaPython();
    
    if (!empty($services)) {
        echo "Sample services:\n";
        foreach (array_slice($services, 0, 3) as $service) {
            echo "  - {$service['name']} ({$service['provider_name']})\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
