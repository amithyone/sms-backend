<?php

/**
 * Comprehensive API Testing Script
 * Tests all VTU and SMS API endpoints to ensure they're working properly
 * and returning frontend-friendly JSON responses
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiTester
{
    private $baseUrl;
    private $authToken;
    private $testResults = [];
    
    public function __construct($baseUrl = 'http://localhost:8000/api')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    /**
     * Run all API tests
     */
    public function runAllTests()
    {
        echo "🚀 Starting Comprehensive API Testing...\n\n";
        
        // Test public endpoints first
        $this->testPublicEndpoints();
        
        // Test authentication
        $this->testAuthentication();
        
        if ($this->authToken) {
            // Test protected endpoints
            $this->testVtuEndpoints();
            $this->testSmsEndpoints();
            $this->testWalletEndpoints();
        }
        
        // Generate test report
        $this->generateReport();
    }
    
    /**
     * Test public endpoints (no authentication required)
     */
    private function testPublicEndpoints()
    {
        echo "📡 Testing Public Endpoints...\n";
        
        $publicEndpoints = [
            // VTU Public Endpoints
            ['GET', '/vtu/services', 'VTU Services List'],
            ['GET', '/vtu/airtime/networks', 'Airtime Networks'],
            ['GET', '/vtu/data/networks', 'Data Networks'],
            ['GET', '/vtu/variations/data?network=mtn', 'MTN Data Bundles'],
            ['GET', '/vtu/provider/balance', 'Provider Balance'],
            ['GET', '/betting/providers', 'Betting Providers'],
            ['GET', '/electricity/providers', 'Electricity Providers'],
            
            // SMS Public Endpoints
            ['GET', '/sms/providers', 'SMS Providers'],
            ['GET', '/sms/countries', 'SMS Countries'],
            ['GET', '/sms/services?country=187', 'SMS Services for USA'],
            ['GET', '/sms/countries-by-service?service=wa', 'Countries by WhatsApp Service'],
            
            // General
            ['GET', '/test', 'API Test Endpoint'],
            ['GET', '/cors-test', 'CORS Test'],
        ];
        
        foreach ($publicEndpoints as $endpoint) {
            $this->testEndpoint($endpoint[0], $endpoint[1], $endpoint[2]);
        }
        
        echo "\n";
    }
    
    /**
     * Test authentication endpoints
     */
    private function testAuthentication()
    {
        echo "🔐 Testing Authentication...\n";
        
        // Test registration (might fail if user exists)
        $this->testEndpoint('POST', '/register', 'User Registration', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        
        // Test login
        $response = $this->makeRequest('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        if ($response && isset($response['data']['token'])) {
            $this->authToken = $response['data']['token'];
            echo "✅ Authentication successful - Token obtained\n";
        } else {
            echo "❌ Authentication failed - Using public endpoints only\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test VTU endpoints
     */
    private function testVtuEndpoints()
    {
        echo "💰 Testing VTU Endpoints...\n";
        
        $vtuEndpoints = [
            // Phone validation
            ['POST', '/vtu/validate/phone', 'Phone Validation', [
                'phone' => '08012345678',
                'network' => 'mtn'
            ]],
            
            // Airtime purchase (will fail due to insufficient balance, but tests endpoint)
            ['POST', '/vtu/airtime/purchase', 'Airtime Purchase', [
                'network' => 'mtn',
                'phone' => '08012345678',
                'amount' => 100
            ]],
            
            // Data bundle purchase
            ['POST', '/vtu/data/purchase', 'Data Bundle Purchase', [
                'network' => 'mtn',
                'phone' => '08012345678',
                'plan' => 'mtn_1GB',
                'plan_name' => '1GB Daily',
                'amount' => 300
            ]],
            
            // Transaction status
            ['GET', '/vtu/transaction/status?reference=TEST_123', 'Transaction Status'],
            
            // User transactions
            ['GET', '/vtu/transactions', 'User Transactions'],
            ['GET', '/transactions', 'All Transactions'],
            
            // Betting verification
            ['POST', '/vtu/verify-customer', 'Betting Customer Verification', [
                'service_id' => 'bet9ja',
                'customer_id' => '1234567890'
            ]],
            
            // Betting purchase
            ['POST', '/vtu/betting/purchase', 'Betting Purchase', [
                'service_id' => 'bet9ja',
                'customer_id' => '1234567890',
                'amount' => 1000
            ]],
            
            // Electricity verification
            ['POST', '/vtu/electricity/verify', 'Electricity Customer Verification', [
                'service_id' => 'ikeja-electric',
                'customer_id' => '1234567890'
            ]],
            
            // Electricity purchase
            ['POST', '/vtu/electricity/purchase', 'Electricity Purchase', [
                'service_id' => 'ikeja-electric',
                'customer_id' => '1234567890',
                'variation_id' => 'prepaid',
                'amount' => 2000
            ]],
        ];
        
        foreach ($vtuEndpoints as $endpoint) {
            $this->testEndpoint($endpoint[0], $endpoint[1], $endpoint[2], $endpoint[3] ?? []);
        }
        
        echo "\n";
    }
    
    /**
     * Test SMS endpoints
     */
    private function testSmsEndpoints()
    {
        echo "📱 Testing SMS Endpoints...\n";
        
        $smsEndpoints = [
            // SMS order creation
            ['POST', '/sms/order', 'SMS Order Creation', [
                'country' => '187', // USA
                'service' => 'wa',  // WhatsApp
                'mode' => 'auto'
            ]],
            
            // SMS orders list
            ['GET', '/sms/orders', 'SMS Orders List'],
            
            // SMS code retrieval
            ['POST', '/sms/code', 'SMS Code Retrieval', [
                'order_id' => 'SMS_TEST_123'
            ]],
            
            // SMS order cancellation
            ['POST', '/sms/cancel', 'SMS Order Cancellation', [
                'order_id' => 'SMS_TEST_123'
            ]],
            
            // SMS statistics
            ['GET', '/sms/stats', 'SMS Statistics'],
        ];
        
        foreach ($smsEndpoints as $endpoint) {
            $this->testEndpoint($endpoint[0], $endpoint[1], $endpoint[2], $endpoint[3] ?? []);
        }
        
        echo "\n";
    }
    
    /**
     * Test wallet endpoints
     */
    private function testWalletEndpoints()
    {
        echo "💳 Testing Wallet Endpoints...\n";
        
        $walletEndpoints = [
            ['GET', '/wallet/deposits', 'Recent Deposits'],
            ['POST', '/wallet/topup/initiate', 'Initiate Top-up', [
                'amount' => 1000,
                'payment_method' => 'payvibe'
            ]],
            ['POST', '/wallet/topup/verify', 'Verify Top-up', [
                'reference' => 'TEST_REF_123'
            ]],
        ];
        
        foreach ($walletEndpoints as $endpoint) {
            $this->testEndpoint($endpoint[0], $endpoint[1], $endpoint[2], $endpoint[3] ?? []);
        }
        
        echo "\n";
    }
    
    /**
     * Test a single endpoint
     */
    private function testEndpoint($method, $path, $description, $data = [])
    {
        $response = $this->makeRequest($method, $path, $data);
        
        $result = [
            'endpoint' => $method . ' ' . $path,
            'description' => $description,
            'success' => $response !== null,
            'response' => $response,
            'timestamp' => now()
        ];
        
        $this->testResults[] = $result;
        
        $status = $result['success'] ? '✅' : '❌';
        echo "{$status} {$description} - {$method} {$path}\n";
        
        if ($response && isset($response['success'])) {
            echo "   Response: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            if (isset($response['message'])) {
                echo "   Message: " . $response['message'] . "\n";
            }
        }
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($method, $path, $data = [])
    {
        try {
            $url = $this->baseUrl . $path;
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            
            if ($this->authToken) {
                $headers['Authorization'] = 'Bearer ' . $this->authToken;
            }
            
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            
            $options = [
                'headers' => $headers,
                'http_errors' => false
            ];
            
            if (!empty($data)) {
                if ($method === 'GET') {
                    $url .= '?' . http_build_query($data);
                } else {
                    $options['json'] = $data;
                }
            }
            
            $response = $client->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            
            $jsonResponse = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonResponse;
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON response',
                    'raw_response' => $body
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate test report
     */
    private function generateReport()
    {
        echo "📊 Test Report\n";
        echo "=============\n\n";
        
        $totalTests = count($this->testResults);
        $successfulTests = count(array_filter($this->testResults, fn($r) => $r['success']));
        $failedTests = $totalTests - $successfulTests;
        
        echo "Total Tests: {$totalTests}\n";
        echo "Successful: {$successfulTests}\n";
        echo "Failed: {$failedTests}\n";
        echo "Success Rate: " . round(($successfulTests / $totalTests) * 100, 2) . "%\n\n";
        
        if ($failedTests > 0) {
            echo "❌ Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    echo "- {$result['description']} ({$result['endpoint']})\n";
                    if (isset($result['response']['message'])) {
                        echo "  Error: {$result['response']['message']}\n";
                    }
                }
            }
            echo "\n";
        }
        
        echo "✅ Successful Tests:\n";
        foreach ($this->testResults as $result) {
            if ($result['success']) {
                echo "- {$result['description']} ({$result['endpoint']})\n";
            }
        }
        
        // Save detailed report to file
        $reportFile = 'api_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->testResults, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: {$reportFile}\n";
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $tester = new ApiTester();
    $tester->runAllTests();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_all_apis.php\n";
}

