<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsService;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FiveSimProvider implements ProviderInterface
{
    private SimpleHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    public function getCountries(SmsService $smsService): array
    {
        $config = $smsService->getApiConfig();
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($config['api_url'] . '/v1/guest/countries');

        if ($resp->successful()) {
            $data = $resp->json();
            if (is_array($data)) {
                $countries = [];
                // New API format: { "countryname": { "iso": {...}, "text_en": "Name", ... }, ... }
                foreach ($data as $countrySlug => $countryInfo) {
                    if (is_array($countryInfo)) {
                        $name = $countryInfo['text_en'] ?? ucfirst($countrySlug);
                        $countries[] = [
                            'code' => $countrySlug,
                            'name' => $name,
                        ];
                    }
                }
                return $countries;
            }
        }

        // Fallback via prices map
        $prices = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/prices');
        if ($prices->successful()) {
            $data = $prices->json();
            if (!is_array($data)) {
                $decoded = json_decode($prices->body(), true);
                $data = is_array($decoded) ? $decoded : [];
            }
            $countryIds = [];
            foreach ($data as $serviceCode => $countries) {
                if (is_array($countries)) {
                    foreach ($countries as $countryCode => $_) {
                        $countryIds[$countryCode] = true;
                    }
                }
            }
            $result = [];
            foreach (array_keys($countryIds) as $cc) {
                $result[] = ['code' => (string)$cc, 'name' => strtoupper((string)$cc)];
            }
            return $result;
        }

        return [];
    }

    public function getServices(SmsService $smsService, string $country): array
    {
        $config = $smsService->getApiConfig();
        // JSON products endpoint first (use 5SIM country slug only if valid)
        $countrySlug = null;
        if (!is_numeric($country)) {
            $countrySlug = strtolower((string)$country);
        } else {
            // Minimal numeric->slug mapping (USA intentionally not mapped as 5SIM does not support it)
            $map = [
                '19' => 'nigeria',
                '38' => 'ghana',
                '31' => 'south-africa',
                '8'  => 'kenya',
                '21' => 'egypt',
                '16' => 'uk',
                '4'  => 'philippines',
                '6'  => 'indonesia',
                '22' => 'india',
            ];
            $countrySlug = $map[(string)$country] ?? null;
        }

        // Validate slug against official 5SIM countries; if invalid, avoid bad requests
        if ($countrySlug) {
            $countriesResp = $this->httpClient
                ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
                ->get($config['api_url'] . '/v1/guest/countries');
            if ($countriesResp->successful()) {
                $list = $countriesResp->json();
                $slugs = [];
                if (is_array($list)) {
                    // New API format: { "countryname": {...}, ... }
                    foreach ($list as $countryKey => $countryInfo) {
                        if (is_array($countryInfo)) {
                            $slugs[strtolower($countryKey)] = true;
                        }
                    }
                }
                if (empty($slugs[$countrySlug])) {
                    $countrySlug = null; // slug not supported by 5SIM
                }
            }
        }

        // Use prices endpoint to get ALL services (including out of stock)
        // This is better than products endpoint which hides services with 0 count
        $response = null;
        if ($countrySlug) {
            $response = $this->httpClient
                ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
                ->get($config['api_url'] . "/v1/guest/prices?country={$countrySlug}");
        } else {
            // No valid slug for 5SIM: return empty to avoid failed number requests
            return [];
        }

        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data) && !empty($data)) {
                // Prices endpoint format: { "country": { "service": { "operator": { "cost": X, "count": Y } } } }
                $out = [];
                $usdPerRub = (float) config('services.sms_fx.usd_per_rub', 0.011);
                
                // Data is nested by country, extract the country data
                $countryData = null;
                foreach ($data as $key => $value) {
                    if (is_array($value) && strtolower($key) === strtolower($countrySlug)) {
                        $countryData = $value;
                        break;
                    }
                }
                
                if ($countryData) {
                    // Now iterate through services
                    foreach ($countryData as $product => $operators) {
                        if (!is_array($operators)) continue;
                        
                        // Aggregate across all operators for this service
                        $minPriceRub = null;
                        $totalQty = 0;
                        
                        foreach ($operators as $operator => $info) {
                            if (!is_array($info)) continue;
                            
                            $priceRub = isset($info['cost']) ? (float)$info['cost'] : 0;
                            $qty = isset($info['count']) ? (int)$info['count'] : 0;
                            
                            if ($priceRub > 0) {
                                $minPriceRub = $minPriceRub === null ? $priceRub : min($minPriceRub, $priceRub);
                            }
                            $totalQty += $qty;
                        }
                        
                        if ($minPriceRub !== null && $minPriceRub > 0) {
                            $usdCost = $minPriceRub * max($usdPerRub, 0.00001);
                            $out[] = [
                                'name' => ucfirst((string)$product),
                                'service' => (string)$product,
                                'cost' => $usdCost,
                                'currency' => 'USD',
                                'count' => $totalQty,
                            ];
                        }
                    }
                }
                
                if (!empty($out)) {
                    return $out;
                }
            }
        }

        // Fallback: handler API getNumbersStatus
        $handlerBase = (string)($config['api_url'] ?? 'http://api1.5sim.net/stubs/handler_api.php');
        if (stripos($handlerBase, 'handler_api.php') === false) {
            $handlerBase = rtrim($handlerBase, '/') . '/stubs/handler_api.php';
        }
        $url = $handlerBase . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getNumbersStatus'
            . '&country=' . urlencode($country);
        $fallbackResp = $this->httpClient->get($url);
        Log::info('5SIM getNumbersStatus HTTP', [ 'url' => $url, 'status' => $fallbackResp->status(), 'body_sample' => substr($fallbackResp->body(), 0, 800) ]);
        if ($fallbackResp->successful()) {
            $json = $fallbackResp->json();
            if (!is_array($json)) {
                $decoded = json_decode($fallbackResp->body(), true);
                $json = is_array($decoded) ? $decoded : [];
            }
            $services = [];
            foreach ($json as $key => $total) {
                if (!is_string($key)) continue;
                $parts = explode('_', $key, 2);
                $code = $parts[0];
                $count = (int)$total;
                $services[] = [ 'name' => strtoupper($code), 'service' => $code, 'cost' => 0, 'count' => $count ];
            }
            // Enrich costs using /v1/guest/prices?product={service}
            if (!empty($services)) {
                $countryName = null;
                $row = DB::table('sms_countries')->where('provider', '5sim')->where('country_id', (string)$country)->first();
                if ($row && isset($row->name)) {
                    $countryName = strtolower($row->name);
                } else {
                    if (is_numeric($country)) {
                        // Map common Tiger-style numeric country IDs to 5SIM country slugs
                        $idToName = [
                            '187' => 'united states',
                            '19' => 'nigeria',
                            '38' => 'ghana',
                            '31' => 'south africa',
                            '8' => 'kenya',
                            '21' => 'egypt',
                            '16' => 'united kingdom',
                            '4' => 'philippines',
                            '6' => 'indonesia',
                            '22' => 'india',
                        ];
                        $countryName = $idToName[(string)$country] ?? null;
                    } else {
                        $countryName = strtolower((string)$country);
                    }
                }
                if ($countryName) {
                    // Enrich costs for a limited subset to avoid long sequential calls
                    $enriched = 0;
                    foreach ($services as &$svc) {
                        if ($enriched >= 20) { break; }
                        $product = $this->mapServiceCodeToProduct($svc['service']);
                        $prices = $this->getPricesByProduct($config, $product, $countryName);
                        if (!empty($prices) && isset($prices[0]['cost'])) {
                            $svc['cost'] = (float)$prices[0]['cost'];
                        }
                        $enriched++;
                    }
                    unset($svc);
                }
            }
            usort($services, fn($a,$b) => ($b['count'] <=> $a['count']));
            return $services;
        }

        return [];
    }

    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        $config = $smsService->getApiConfig();
        // Prefer official JSON API (avoids Cloudflare on handler API)
        $apiBase = rtrim((string)($config['api_url'] ?? 'https://5sim.net'), '/');

        // Determine country slug; validate against official list
        $countrySlug = null;
        if (!is_numeric($country)) { $countrySlug = strtolower((string)$country); }
        $countriesResp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($apiBase . '/v1/guest/countries');
        if ($countriesResp->successful()) {
            $valid = [];
            $list = $countriesResp->json();
            if (is_array($list)) {
                // New API format: { "countryname": {...}, ... }
                foreach ($list as $countryKey => $countryInfo) {
                    if (is_array($countryInfo)) {
                        $valid[strtolower($countryKey)] = true;
                    }
                }
            }
            if (!$countrySlug || empty($valid[$countrySlug])) {
                // Reject if unsupported to avoid guaranteed failures
                throw new Exception('5SIM does not support country: ' . (string)$country);
            }
        }

        $product = $this->mapServiceCodeToProduct($service);
        $buyUrl = $apiBase . '/v1/user/buy/activation/' . urlencode($countrySlug) . '/any/' . urlencode($product);
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($buyUrl);
        Log::info('5SIM buy activation HTTP', [ 'url' => 'GET /v1/user/buy/activation/***', 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 500) ]);

        if ($resp->successful()) {
            $data = $resp->json();
            if (is_array($data) && !empty($data['id']) && !empty($data['phone'])) {
                return [
                    'order_id' => (string)$data['id'],
                    'phone_number' => (string)$data['phone'],
                    'cost' => 0,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(20)
                ];
            }
        }

        throw new Exception('Failed to create 5Sim order: HTTP ' . $resp->status());
    }

    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        $config = $smsService->getApiConfig();
        $apiBase = rtrim((string)($config['api_url'] ?? 'https://5sim.net'), '/');
        
        // Use new v1 API to check order status
        $url = $apiBase . '/v1/user/check/' . urlencode($orderId);
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($url);

        if ($resp->successful()) {
            $data = $resp->json();
            
            // Check if SMS has been received
            if (is_array($data) && isset($data['sms'])) {
                $sms = $data['sms'];
                
                // Handle array of SMS messages
                if (is_array($sms) && !empty($sms)) {
                    // Get the most recent SMS code
                    $lastSms = end($sms);
                    if (is_array($lastSms) && isset($lastSms['code'])) {
                        return (string)$lastSms['code'];
                    }
                    // Fallback: extract from text
                    if (is_array($lastSms) && isset($lastSms['text'])) {
                        preg_match('/\b\d{4,8}\b/', $lastSms['text'], $matches);
                        return $matches[0] ?? null;
                    }
                }
                
                // Status is still waiting
                if ($data['status'] === 'PENDING' || $data['status'] === 'RECEIVED') {
                    return null;
                }
            }
        }
        
        return null;
    }

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $config = $smsService->getApiConfig();
        $apiBase = rtrim((string)($config['api_url'] ?? 'https://5sim.net'), '/');
        
        // Use new v1 API to cancel order
        $url = $apiBase . '/v1/user/cancel/' . urlencode($orderId);
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($url);

        if ($resp->successful()) {
            $data = $resp->json();
            // API returns the order with updated status
            if (is_array($data) && isset($data['status'])) {
                return in_array($data['status'], ['CANCELED', 'CANCELLED', 'CANCEL']);
            }
        }
        
        return false;
    }

    public function getBalance(SmsService $smsService): float
    {
        $config = $smsService->getApiConfig();
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->get($config['api_url'] . '/v1/user/profile');
        if ($response->successful()) {
            $data = $response->json();
            $balanceRub = $data['balance'] ?? 0.0;
            
            // Convert Russian Rubles to USD for dashboard display
            // 5sim balance is in RUB, we need to convert to USD
            $usdPerRub = (float) config('services.sms_fx.usd_per_rub', 0.011);
            $balanceUsd = $balanceRub * max($usdPerRub, 0.00001);
            
            return $balanceUsd;
        }
        return 0.0;
    }

    private function getPricesByProduct(array $config, string $product, ?string $countryName = null): array
    {
        $resp = $this->httpClient
            ->withHeaders(['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/prices?product=' . urlencode($product));
        if (!$resp->successful()) return [];
        $json = $resp->json();
        if (!is_array($json)) {
            $decoded = json_decode($resp->body(), true);
            $json = is_array($decoded) ? $decoded : [];
        }
        $root = $json[$product] ?? null;
        if (!is_array($root)) return [];

        $collectForCountry = function (array $countryBlock) {
            $minCost = null; $maxCount = 0;
            foreach ($countryBlock as $variant) {
                if (is_array($variant)) {
                    $cost = isset($variant['cost']) ? (float)$variant['cost'] : null;
                    $count = isset($variant['count']) ? (int)$variant['count'] : 0;
                    if ($cost !== null) {
                        $minCost = $minCost === null ? $cost : min($minCost, $cost);
                        $maxCount = max($maxCount, $count);
                    }
                }
            }
            return [$minCost ?? 0.0, $maxCount];
        };

        if ($countryName) {
            $key = strtolower($countryName);
            $block = $root[$key] ?? null;
            if (!is_array($block)) {
                foreach ($root as $ck => $cb) {
                    if (strtolower($ck) === $key) { $block = $cb; break; }
                }
            }
            if (is_array($block)) {
                [$cost, $count] = $collectForCountry($block);
                return [[ 'service' => $product, 'name' => ucfirst($product), 'cost' => $cost, 'count' => $count ]];
            }
            return [];
        }

        $result = [];
        foreach ($root as $countryKey => $block) {
            if (!is_array($block)) continue;
            [$cost, $count] = $collectForCountry($block);
            $result[] = [ 'country' => $countryKey, 'service' => $product, 'name' => ucfirst($product), 'cost' => $cost, 'count' => $count ];
        }
        return $result;
    }

    /**
     * Map generic service code to 5SIM product slug where they differ.
     */
    private function mapServiceCodeToProduct(string $code): string
    {
        $map = [
            'wa' => 'whatsapp',
            'fb' => 'facebook',
            'ig' => 'instagram',
            'go' => 'google',
            'aky' => 'google',
            'tg' => 'telegram',
            'tw' => 'twitter',
            'rm' => 'facebook',
            'mc' => 'microsoft',
            'cd' => 'spotify',
            'xz' => 'payoneer',
            'yo' => 'amazon',
            'ij' => 'robinhood',
            'bp' => 'gojek',
            'ub' => 'ubisoft',
            'wa_0' => 'whatsapp',
        ];
        return $map[$code] ?? $code;
    }

    // (duplicate removed)
}
