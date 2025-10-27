<?php
// Simple standalone script: Fetch Dassy raw getPrices and compute NGN = USD * 1.5 * 1600

$envFile = '/var/www/api.fadsms.com/.env';
$apiKey = null;
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'DASSY_API_KEY=') === 0) {
            $apiKey = trim(substr($line, 14), " \"'\r\n");
            break;
        }
    }
}
if (!$apiKey) {
    fwrite(STDERR, "Missing DASSY_API_KEY in {$envFile}\n");
    exit(2);
}

$url = 'https://daisysms.com/stubs/handler_api.php?action=getPrices&api_key=' . urlencode($apiKey);
$raw = @file_get_contents($url);
if ($raw === false) {
    fwrite(STDERR, "Failed to fetch from Dassy API\n");
    exit(3);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Non-JSON response from Dassy. First 300 bytes below:\n");
    echo substr($raw, 0, 300), "\n";
    exit(4);
}

$fx = 1600.0;         // FX NGN per USD
$markup = 0.5;        // 50%
$filter = isset($argv[1]) ? strtoupper($argv[1]) : '';
$limit = isset($argv[2]) ? max(0, (int)$argv[2]) : 0; // optional limit

$country = '187'; // US per Dassy/activate tables
$services = isset($data[$country]) && is_array($data[$country]) ? $data[$country] : [];

$rows = [];
foreach ($services as $code => $row) {
    $name = strtoupper((string)$code);
    if ($filter && strpos($name, $filter) === false) continue;
    $usd = isset($row['cost']) ? (float)$row['cost'] : 0.0;
    $qty = isset($row['count']) ? (int)$row['count'] : 0;
    $ngn = (int) ceil($usd * (1.0 + $markup) * $fx);
    $rows[] = [
        'country' => $country,
        'service' => $name,
        'usd' => $usd,
        'count' => $qty,
        'ngn' => $ngn,
    ];
}

if ($limit > 0) {
    $rows = array_slice($rows, 0, $limit);
}

if (empty($rows)) {
    echo "No matches.\n";
    exit(0);
}

foreach ($rows as $r) {
    printf(
        "%s | %s | USD %.4f | Count %d | NGN(USD*1.5*1600): %s\n",
        $r['country'],
        $r['service'],
        $r['usd'],
        $r['count'],
        number_format($r['ngn'])
    );
}


