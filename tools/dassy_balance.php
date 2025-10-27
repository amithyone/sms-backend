<?php
// Simple script to print Dassy (DaisySMS) balance using .env credentials

$env = @file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$base = '';
$key = '';
if ($env) {
    foreach ($env as $line) {
        if (strpos($line, 'DASSY_BASE_URL=') === 0) {
            $base = trim(substr($line, strlen('DASSY_BASE_URL=')), " \"'\r\n");
        } elseif (strpos($line, 'DASSY_API_KEY=') === 0) {
            $key = trim(substr($line, strlen('DASSY_API_KEY=')), " \"'\r\n");
        }
    }
}
if ($base === '') { $base = 'https://daisysms.com/stubs/handler_api.php'; }
if ($key === '') { fwrite(STDERR, "Missing DASSY_API_KEY in .env\n"); exit(2); }

$url = $base . '?api_key=' . urlencode($key) . '&action=getBalance';
$raw = @file_get_contents($url);
if ($raw === false) { fwrite(STDERR, "Failed to fetch getBalance\n"); exit(3); }
$body = trim($raw);
// Expected: ACCESS_BALANCE:123.45
if (stripos($body, 'ACCESS_BALANCE:') === 0) {
    $parts = explode(':', $body, 2);
    $balance = isset($parts[1]) ? trim($parts[1]) : '0';
    echo $balance, "\n";
    exit(0);
}
echo $body, "\n";
exit(0);



