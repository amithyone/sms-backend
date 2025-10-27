<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

echo "🔍 Debugging VTU.ng API...\n\n";

$username = 'faddedog@gmail.com';
$password = 'FxD9VbrA259BuXy';

try {
    $client = new Client();
    
    // Step 1: Get JWT token
    echo "1. Getting JWT token...\n";
    $jwtUrl = 'https://vtu.ng/wp-json/jwt-auth/v1/token';
    $authResponse = $client->post($jwtUrl, [
        'json' => [
            'username' => $username,
            'password' => $password
        ],
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]
    ]);
    
    echo "Auth response status: " . $authResponse->getStatusCode() . "\n";
    echo "Auth response body: " . $authResponse->getBody() . "\n\n";
    
    if ($authResponse->getStatusCode() === 200) {
        $authData = json_decode($authResponse->getBody(), true);
        $token = $authData['token'] ?? null;
        
        if ($token) {
            echo "✅ JWT token obtained: " . substr($token, 0, 20) . "...\n\n";
            
            // Step 2: Get balance
            echo "2. Getting balance...\n";
            $balanceResponse = $client->get('https://vtu.ng/wp-json/api/v2/balance', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ]
            ]);
            
            echo "Balance response status: " . $balanceResponse->getStatusCode() . "\n";
            echo "Balance response body: " . $balanceResponse->getBody() . "\n\n";
            
            // Try to extract balance from error message
            $errorBody = $balanceResponse->getBody();
            if (preg_match('/NGN([0-9,]+\.?[0-9]*)/', $errorBody, $matches)) {
                $balance = str_replace(',', '', $matches[1]);
                echo "✅ Extracted balance from error message: NGN " . number_format($balance, 2) . "\n";
            } else {
                echo "❌ Could not extract balance from error message\n";
            }
            
        } else {
            echo "❌ No token in response\n";
        }
    } else {
        echo "❌ Auth failed: " . $authResponse->getStatusCode() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
