<?php
// Fetch Dassy raw prices and upsert into sms_service_country_prices with USD currency

$envFile = __DIR__ . '/../.env';
$cfg = [];
foreach (['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DASSY_BASE_URL','DASSY_API_KEY'] as $k) {
    $cfg[$k] = '';
}
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        foreach (array_keys($cfg) as $key) {
            if (strpos($line, $key . '=') === 0) {
                $cfg[$key] = trim(substr($line, strlen($key) + 1), " \"'\r\n");
            }
        }
    }
}
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['DB_HOST'] ?: '127.0.0.1', $cfg['DB_PORT'] ?: '3306', $cfg['DB_DATABASE']);
$pdo = new PDO($dsn, $cfg['DB_USERNAME'], $cfg['DB_PASSWORD'], [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);

$base = $cfg['DASSY_BASE_URL'] ?: 'https://daisysms.com/stubs/handler_api.php';
$api = $cfg['DASSY_API_KEY'];
$url = $base . '?action=getPrices&api_key=' . urlencode($api);
$raw = @file_get_contents($url);
if ($raw === false) { fwrite(STDERR, "Failed to fetch Dassy prices\n"); exit(1);} 
$json = json_decode($raw, true);
if (!is_array($json)) { fwrite(STDERR, "Non-JSON response\n"); echo substr($raw,0,300)."\n"; exit(2);} 

$country = '187'; // US for Dassy
$rows = $json[$country] ?? [];
$now = date('Y-m-d H:i:s');
$ins = $pdo->prepare('INSERT INTO sms_service_country_prices (provider, country_code, service, cost, count, provider_currency, last_seen_at, updated_at, created_at) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE cost=VALUES(cost), count=VALUES(count), provider_currency=VALUES(provider_currency), last_seen_at=VALUES(last_seen_at), updated_at=VALUES(updated_at)');

$n = 0;
foreach ($rows as $service => $data) {
    if (!is_array($data)) continue;
    $usd = isset($data['cost']) ? (float)$data['cost'] : 0.0;
    $count = isset($data['count']) ? (int)$data['count'] : 0;
    $ins->execute(['dassy', $country, (string)$service, $usd, $count, 'USD', $now, $now, $now]);
    $n++;
}
echo "Upserted {$n} Dassy rows for country {$country}.\n";

