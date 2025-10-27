<?php
// Test script to verify domain detection logic

// Simulate different referer headers
$testCases = [
    'https://fadsms.com/login' => 'https://fadsms.com',
    'https://faddedsms.com/login' => 'https://faddedsms.com',
    'https://fadsms.com/register' => 'https://fadsms.com',
    'https://faddedsms.com/register' => 'https://faddedsms.com',
    'https://www.fadsms.com/login' => 'https://fadsms.com',
    'https://www.faddedsms.com/login' => 'https://faddedsms.com',
    'https://subdomain.fadsms.com/login' => 'https://fadsms.com',
    'https://subdomain.faddedsms.com/login' => 'https://faddedsms.com',
    '' => 'https://fadsms.com', // default
    'https://otherdomain.com/login' => 'https://fadsms.com', // default
];

echo "Testing domain detection logic:\n";
echo "================================\n\n";

foreach ($testCases as $referer => $expectedUrl) {
    $frontendUrl = 'https://fadsms.com'; // default
    
    // Check if user came from faddedsms.com
    if (strpos($referer, 'faddedsms.com') !== false) {
        $frontendUrl = 'https://faddedsms.com';
    }
    
    $status = ($frontendUrl === $expectedUrl) ? '✅ PASS' : '❌ FAIL';
    echo "Referer: " . ($referer ?: '(empty)') . "\n";
    echo "Expected: $expectedUrl\n";
    echo "Got: $frontendUrl\n";
    echo "Status: $status\n\n";
}

echo "Domain detection test completed!\n";
?>
