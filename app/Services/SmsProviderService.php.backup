<?php

namespace App\Services;

use App\Models\SmsService;
use App\Models\SmsOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\SimpleHttpClient;

class SmsProviderService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    /**
     * Get available countries from SMS provider
     */
    public function getCountries(SmsService $smsService): array
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimCountries($config);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassyCountries($config);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsCountries($config);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error getting countries from {$smsService->provider}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available services for a country
     */
    public function getServices(SmsService $smsService, string $country): array
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimServices($config, $country);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassyServices($config, $country);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsServices($config, $country);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error getting services from {$smsService->provider}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new SMS order
     */
    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->create5SimOrder($config, $country, $service);
                case SmsService::PROVIDER_DASSY:
                    return $this->createDassyOrder($config, $country, $service);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->createTigerSmsOrder($config, $country, $service);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error creating order with {$smsService->provider}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get SMS code for an order
     */
    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimSmsCode($config, $orderId);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassySmsCode($config, $orderId);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsCode($config, $orderId);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error getting SMS code from {$smsService->provider}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel an SMS order
     */
    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->cancel5SimOrder($config, $orderId);
                case SmsService::PROVIDER_DASSY:
                    return $this->cancelDassyOrder($config, $orderId);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->cancelTigerSmsOrder($config, $orderId);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error cancelling order with {$smsService->provider}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get provider notifications/flash messages (e.g., maintenance notices)
     */
    public function getNotifications(SmsService $smsService, string $lang = 'en'): array
    {
        try {
            $config = $smsService->getApiConfig();
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimNotifications($config, $lang);
                default:
                    return [];
            }
        } catch (Exception $e) {
            Log::error("Error getting notifications from {$smsService->provider}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(SmsService $smsService): float
    {
        try {
            $config = $smsService->getApiConfig();
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimBalance($config);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassyBalance($config);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsBalance($config);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error getting balance from {$smsService->provider}: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Return countries that support a given service, with optional price/count.
     * Normalized output: [{ country_id, country_name, cost, count }]
     */
    public function getCountriesByService(SmsService $smsService, string $serviceCodeOrName): array
    {
        $config = $smsService->getApiConfig();
        switch ($smsService->provider) {
            case SmsService::PROVIDER_TIGER_SMS:
                // Fetch full price map once
                $mapUrl = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getPrices';
                $resp = $this->httpClient->get($mapUrl);
                if (!$resp->successful()) return [];
                $data = $resp->json();
                if (!is_array($data)) {
                    $decoded = json_decode($resp->body(), true);
                    $data = is_array($decoded) ? $decoded : [];
                }
                $serviceKey = strtolower($serviceCodeOrName);
                $rows = [];
                foreach ($data as $countryId => $servicesMap) {
                    if (!is_array($servicesMap)) continue;
                    if (!isset($servicesMap[$serviceKey])) continue;
                    $entry = $servicesMap[$serviceKey];
                    $cost = null; $count = 0;
                    if (is_array($entry)) {
                        if (isset($entry['cost']) || isset($entry['price'])) {
                            $cost = (float)($entry['cost'] ?? $entry['price']);
                            $count = (int)($entry['count'] ?? $entry['available'] ?? $entry['phones'] ?? 0);
                        } else {
                            foreach ($entry as $variant) {
                                if (is_array($variant)) {
                                    $vc = isset($variant['cost']) ? (float)$variant['cost'] : (isset($variant['price']) ? (float)$variant['price'] : null);
                                    $vn = (int)($variant['count'] ?? $variant['available'] ?? $variant['phones'] ?? 0);
                                    if ($vc !== null) {
                                        $cost = $cost === null ? $vc : min($cost, $vc);
                                        $count = max($count, $vn);
                                    }
                                }
                            }
                        }
                    }
                    $rows[] = [
                        'country_id' => (string)$countryId,
                        'country_name' => DB::table('sms_countries')->where('provider','tiger_sms')->where('country_id',(string)$countryId)->value('name') ?? ('Country ' . $countryId),
                        'cost' => $cost ?? 0,
                        'count' => $count,
                    ];
                }
                // Sort by cost asc then count desc
                usort($rows, function($a,$b){ $cmp = ($a['cost'] <=> $b['cost']); return $cmp !== 0 ? $cmp : ($b['count'] <=> $a['count']); });
                return $rows;

            case SmsService::PROVIDER_5SIM:
                // Use prices by product; resolve country_id from curated table when possible
                $product = strtolower($serviceCodeOrName);
                $list = $this->get5SimPricesByProduct($config, $product, null);
                if (empty($list)) return [];
                $rows = [];
                foreach ($list as $row) {
                    $name = strtolower((string)($row['country'] ?? ''));
                    if ($name === '') continue;
                    // map name to id if available
                    $mapped = DB::table('sms_countries')->where('provider','5sim')->whereRaw('LOWER(name)=?', [$name])->first();
                    $rows[] = [
                        'country_id' => $mapped ? (string)$mapped->country_id : $name,
                        'country_name' => $mapped ? $mapped->name : ucfirst($name),
                        'cost' => (float)($row['cost'] ?? 0),
                        'count' => (int)($row['count'] ?? 0),
                    ];
                }
                usort($rows, function($a,$b){ $cmp = ($a['cost'] <=> $b['cost']); return $cmp !== 0 ? $cmp : ($b['count'] <=> $a['count']); });
                return $rows;
        }
        return [];
    }

    // 5Sim API Methods
    private function get5SimCountries(array $config): array
    {
        // Primary: try official countries endpoint (if available)
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/countries');

        if ($resp->successful()) {
            $data = $resp->json();
            if (is_array($data)) {
                return collect($data)->map(function ($country) {
                    return [
                        'code' => $country['country'] ?? ($country['code'] ?? null),
                        'name' => $country['title'] ?? ($country['name'] ?? ''),
                    ];
                })->filter(fn($c) => $c['code'] && $c['name'])->values()->toArray();
            }
        }

        // Fallback: derive from prices map
        $prices = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/prices');
        if ($prices->successful()) {
            $data = $prices->json();
            if (!is_array($data)) {
                $decoded = json_decode($prices->body(), true);
                $data = is_array($decoded) ? $decoded : [];
            }
            // Expect shape: service -> country -> object; we need country keys
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

    private function get5SimServices(array $config, string $country): array
    {
        // Primary (JSON API): products by country/operator
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . "/v1/guest/products/{$country}/any");

        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data) && !empty($data)) {
                return collect($data)->map(function ($service) {
                    return [
                        'name' => $service['name'],
                        'service' => $service['service'],
                        'cost' => $service['price'],
                        'count' => $service['count'] ?? 0
                    ];
                })->toArray();
            }
        }

        // Fallback (handler API): getNumbersStatus
        $handlerBase = (string)($config['api_url'] ?? '');
        if (stripos($handlerBase, 'handler_api.php') === false) {
            $handlerBase = 'http://api1.5sim.net/stubs/handler_api.php';
        }
        $url = $handlerBase . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getNumbersStatus'
            . '&country=' . urlencode($country);
        $fallbackResp = $this->httpClient->get($url);
        \Log::info('5SIM getNumbersStatus HTTP', [
            'url' => $url,
            'status' => $fallbackResp->status(),
            'body_sample' => substr($fallbackResp->body(), 0, 800),
        ]);
        if ($fallbackResp->successful()) {
            $json = $fallbackResp->json();
            if (!is_array($json)) {
                $decoded = json_decode($fallbackResp->body(), true);
                $json = is_array($decoded) ? $decoded : [];
            }
            $services = [];
            foreach ($json as $key => $total) {
                // key like "wa_0" → service code is before underscore
                if (!is_string($key)) continue;
                $parts = explode('_', $key, 2);
                $code = $parts[0];
                $count = (int)$total;
                $services[] = [
                    'name' => strtoupper($code),
                    'service' => $code,
                    'cost' => 0,
                    'count' => $count,
                ];
            }
            // Try enrich costs using /v1/guest/prices?product={service} for this country (by name)
            if (!empty($services)) {
                // map country code to lowercase name from curated sms_countries if available
                $countryName = null;
                $row = DB::table('sms_countries')->where('provider', '5sim')->where('country_id', (string)$country)->first();
                if ($row && isset($row->name)) {
                    $countryName = strtolower($row->name);
                } else {
                    $countryName = is_numeric($country) ? null : strtolower((string)$country);
                }
                if ($countryName) {
                    foreach ($services as &$svc) {
                        $product = $svc['service'];
                        $prices = $this->get5SimPricesByProduct($config, $product, $countryName);
                        if (!empty($prices) && isset($prices[0]['cost'])) {
                            $svc['cost'] = (float)$prices[0]['cost'];
                        }
                    }
                    unset($svc);
                }
            }
            // Sort by availability desc
            usort($services, function ($a, $b) { return ($b['count'] <=> $a['count']); });
            return $services;
        }

        return [];
    }

    /**
     * 5SIM Products by country/operator (activation & hosting)
     * GET /v1/guest/products/{country}/{operator}
     * Normalizes to: [{ service, name, cost, count, category }]
     */
    private function get5SimProducts(array $config, string $country, string $operator = 'any'): array
    {
        $resp = $this->httpClient
            ->withHeaders(['Accept' => 'application/json'])
            ->get($config['api_url'] . "/v1/guest/products/{$country}/{$operator}");

        if (!$resp->successful()) {
            return [];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            $decoded = json_decode($resp->body(), true);
            $json = is_array($decoded) ? $decoded : [];
        }

        $out = [];
        foreach ($json as $product => $info) {
            if (!is_array($info)) continue;
            $out[] = [
                'service' => (string)$product,
                'name' => ucfirst((string)$product),
                'cost' => isset($info['Price']) ? (float)$info['Price'] : 0,
                'count' => isset($info['Qty']) ? (int)$info['Qty'] : 0,
                'category' => $info['Category'] ?? null,
            ];
        }
        // Sort by cost asc
        usort($out, function ($a, $b) { return ($a['cost'] <=> $b['cost']); });
        return $out;
    }

    /**
     * 5SIM All prices map
     * GET /v1/guest/prices
     * Returns a flattened list: [{ service, country, cost, count }]
     */
    private function get5SimAllPrices(array $config): array
    {
        $resp = $this->httpClient
            ->withHeaders(['Accept' => 'application/json'])
            ->get($config['api_url'] . '/v1/guest/prices');

        if (!$resp->successful()) return [];
        $json = $resp->json();
        if (!is_array($json)) {
            $decoded = json_decode($resp->body(), true);
            $json = is_array($decoded) ? $decoded : [];
        }

        $rows = [];
        foreach ($json as $service => $countries) {
            if (!is_array($countries)) continue;
            foreach ($countries as $countryName => $variants) {
                if (!is_array($variants)) continue;
                $minCost = null; $maxCount = 0;
                foreach ($variants as $variant) {
                    if (!is_array($variant)) continue;
                    $cost = isset($variant['cost']) ? (float)$variant['cost'] : null;
                    $count = isset($variant['count']) ? (int)$variant['count'] : 0;
                    if ($cost !== null) {
                        $minCost = $minCost === null ? $cost : min($minCost, $cost);
                        $maxCount = max($maxCount, $count);
                    }
                }
                $rows[] = [
                    'service' => (string)$service,
                    'country' => (string)$countryName,
                    'cost' => $minCost ?? 0,
                    'count' => $maxCount,
                ];
            }
        }
        return $rows;
    }

    /**
     * 5SIM: Get prices by product (service) across countries, or for a specific country name
     * Endpoint sample: GET /v1/guest/prices?product=facebook
     * Response shape:
     * {
     *   "facebook": {
     *     "afghanistan": { "virtual18": { "cost":4, "count":1260, "rate":99.99 }, ... }
     *   }
     * }
     */
    private function get5SimPricesByProduct(array $config, string $product, ?string $countryName = null): array
    {
        $resp = $this->httpClient
            ->withHeaders(['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/prices?product=' . urlencode($product));

        if (!$resp->successful()) {
            return [];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            $decoded = json_decode($resp->body(), true);
            $json = is_array($decoded) ? $decoded : [];
        }

        $root = $json[$product] ?? null;
        if (!is_array($root)) {
            return [];
        }

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

        // If a specific country name was provided, filter to it
        if ($countryName) {
            $key = strtolower($countryName);
            $block = $root[$key] ?? null;
            if (!is_array($block)) {
                // try other common variants
                foreach ($root as $ck => $cb) {
                    if (strtolower($ck) === $key) { $block = $cb; break; }
                }
            }
            if (is_array($block)) {
                [$cost, $count] = $collectForCountry($block);
                return [[
                    'service' => $product,
                    'name' => ucfirst($product),
                    'cost' => $cost,
                    'count' => $count,
                ]];
            }
            return [];
        }

        // Otherwise, return a list across all countries
        $result = [];
        foreach ($root as $countryKey => $block) {
            if (!is_array($block)) continue;
            [$cost, $count] = $collectForCountry($block);
            $result[] = [
                'country' => $countryKey,
                'service' => $product,
                'name' => ucfirst($product),
                'cost' => $cost,
                'count' => $count,
            ];
        }
        return $result;
    }

    private function create5SimOrder(array $config, string $country, string $service): array
    {
        // Use handler API (Cloudflare-free)
        $handlerBase = (string)($config['api_url'] ?? 'http://api1.5sim.net/stubs/handler_api.php');
        if (stripos($handlerBase, 'handler_api.php') === false) {
            $handlerBase = rtrim($handlerBase, '/') . '/stubs/handler_api.php';
        }
        $url = $handlerBase
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getNumber'
            . '&service=' . urlencode($service)
            . '&country=' . urlencode($country);

        $resp = $this->httpClient->get($url);
        $body = trim($resp->body());
        \Log::info('5SIM getNumber HTTP', [
            'url' => $url,
            'status' => $resp->status(),
            'body_sample' => substr($body, 0, 300),
        ]);

        if ($resp->successful() && stripos($body, 'ACCESS_NUMBER') === 0) {
            $parts = explode(':', $body);
            $orderId = $parts[1] ?? null;
            $phone = $parts[2] ?? null;
            if ($orderId && $phone) {
                return [
                    'order_id' => $orderId,
                    'phone_number' => $phone,
                    'cost' => 0,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(20)
                ];
            }
        }

        throw new Exception('Failed to create 5Sim order: ' . $body);
    }

    private function get5SimSmsCode(array $config, string $orderId): ?string
    {
        // Handler API: action=getStatus&id=...
        $handlerBase = (string)($config['api_url'] ?? 'http://api1.5sim.net/stubs/handler_api.php');
        if (stripos($handlerBase, 'handler_api.php') === false) {
            $handlerBase = rtrim($handlerBase, '/') . '/stubs/handler_api.php';
        }
        $url = $handlerBase
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getStatus'
            . '&id=' . urlencode($orderId);

        $resp = $this->httpClient->get($url);
        $body = trim($resp->body());
        if ($resp->successful()) {
            if (stripos($body, 'STATUS_OK') === 0) {
                $parts = explode(':', $body, 2);
                return isset($parts[1]) ? trim($parts[1]) : null;
            }
            if (stripos($body, 'STATUS_WAIT') === 0) {
                return null;
            }
        }
        return null;
    }

    private function cancel5SimOrder(array $config, string $orderId): bool
    {
        // Handler API: setStatus status=8
        $handlerBase = (string)($config['api_url'] ?? 'http://api1.5sim.net/stubs/handler_api.php');
        if (stripos($handlerBase, 'handler_api.php') === false) {
            $handlerBase = rtrim($handlerBase, '/') . '/stubs/handler_api.php';
        }
        $url = $handlerBase
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=setStatus'
            . '&id=' . urlencode($orderId)
            . '&status=8';

        $resp = $this->httpClient->get($url);
        $body = trim($resp->body());
        return $resp->successful() && (
            stripos($body, 'ACCESS_CANCEL') === 0 || stripos($body, 'STATUS_CANCEL') === 0 || stripos($body, 'ACCESS') === 0
        );
    }

    private function get5SimBalance(array $config): float
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/user/profile');

        if ($response->successful()) {
            $data = $response->json();
            return $data['balance'] ?? 0.0;
        }

        return 0.0;
    }

    private function get5SimNotifications(array $config, string $lang = 'en'): array
    {
        $resp = $this->httpClient
            ->withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Accept' => 'application/json',
            ])
            ->get($config['api_url'] . '/v1/guest/flash/' . urlencode($lang));

        if ($resp->successful()) {
            $json = $resp->json();
            if (is_array($json) && isset($json['text'])) {
                return ['text' => (string)$json['text']];
            }
        }
        return [];
    }

    // Dassy API Methods
    private function getDassyCountries(array $config): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/countries');

        if ($response->successful()) {
            $data = $response->json();
            return collect($data)->map(function ($country) {
                return [
                    'code' => $country['code'],
                    'name' => $country['name'],
                    'flag' => $country['flag'] ?? null
                ];
            })->toArray();
        }

        return [];
    }

    private function getDassyServices(array $config, string $country): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . "/services/{$country}");

        if ($response->successful()) {
            $data = $response->json();
            return collect($data)->map(function ($service) {
                return [
                    'name' => $service['name'],
                    'service' => $service['service'],
                    'cost' => $service['price'],
                    'count' => $service['available'] ?? 0
                ];
            })->toArray();
        }

        return [];
    }

    private function createDassyOrder(array $config, string $country, string $service): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->post($config['api_url'] . '/order', [
                'country' => $country,
                'service' => $service
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['order_id'],
                'phone_number' => $data['phone'],
                'cost' => $data['cost'],
                'status' => 'active',
                'expires_at' => now()->addMinutes(20)
            ];
        }

        throw new Exception('Failed to create Dassy order: ' . $response->body());
    }

    private function getDassySmsCode(array $config, string $orderId): ?string
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . "/order/{$orderId}/code");

        if ($response->successful()) {
            $data = $response->json();
            return $data['code'] ?? null;
        }

        return null;
    }

    private function cancelDassyOrder(array $config, string $orderId): bool
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->post($config['api_url'] . "/order/{$orderId}/cancel");

        return $response->successful();
    }

    private function getDassyBalance(array $config): float
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/balance');

        if ($response->successful()) {
            $data = $response->json();
            return $data['balance'] ?? 0.0;
        }

        return 0.0;
    }

    // Tiger SMS API Methods
    private function getTigerSmsCountries(array $config): array
    {
        // Tiger SMS handler API: action=getCountries returns JSON
        $url = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getCountries';
        $response = $this->httpClient->get($url);
        \Log::info('TigerSMS getCountries HTTP', [
            'url' => $this->sanitizeUrl($url),
            'status' => $response->status(),
            'body_sample' => substr($response->body(), 0, 1000),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!is_array($data)) {
                $decoded = json_decode($response->body(), true);
                $data = is_array($decoded) ? $decoded : [];
            }
            if (!empty($data)) {
                $countries = [];
                // Prefer numeric keys (country IDs)
                foreach ($data as $key => $entry) {
                    if (is_numeric($key) && is_array($entry)) {
                        $code = (string)$key;
                        $name = $entry['eng'] ?? ($entry['english'] ?? ($entry['name'] ?? 'Country ' . $code));
                        $countries[] = ['code' => $code, 'name' => $name];
                    }
                }
                // If none captured, try objects with id field
                if (empty($countries)) {
                    foreach ($data as $entry) {
                        if (is_array($entry) && isset($entry['id']) && is_numeric($entry['id'])) {
                            $code = (string)$entry['id'];
                            $name = $entry['eng'] ?? ($entry['english'] ?? ($entry['name'] ?? 'Country ' . $code));
                            $countries[] = ['code' => $code, 'name' => $name];
                        }
                    }
                }
                if (!empty($countries)) {
                    usort($countries, function ($a, $b) { return strcmp($a['name'], $b['name']); });
                    return $countries;
                }
            }
        }

        // Fallback: derive countries from getPrices (no params) and map common IDs to names
        $pricesUrl = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getPrices';
        $pricesResp = $this->httpClient->get($pricesUrl);
        \Log::info('TigerSMS getPrices (countries fallback) HTTP', [
            'url' => $this->sanitizeUrl($pricesUrl),
            'status' => $pricesResp->status(),
            'body_sample' => substr($pricesResp->body(), 0, 1000),
        ]);
        if ($pricesResp->successful()) {
            $data = $pricesResp->json();
            if (!is_array($data)) {
                $decoded = json_decode($pricesResp->body(), true);
                $data = is_array($decoded) ? $decoded : [];
            }
            $countryIds = [];
            // Tiger returns top-level keys as numeric country IDs when no country param
            foreach ($data as $maybeCountryId => $servicesMap) {
                if (is_numeric($maybeCountryId) && is_array($servicesMap)) {
                    $countryIds[(string)$maybeCountryId] = true;
                }
            }
            $idToName = [
                '19' => 'Nigeria', '38' => 'Ghana', '31' => 'South Africa', '8' => 'Kenya', '21' => 'Egypt',
                '16' => 'United Kingdom', '187' => 'United States', '4' => 'Philippines', '6' => 'Indonesia', '22' => 'India',
            ];
            $countries = [];
            foreach (array_keys($countryIds) as $id) {
                $countries[] = [
                    'code' => (string)$id,
                    'name' => $idToName[(string)$id] ?? ('Country ' . (string)$id),
                ];
            }
            // Sort by name
            usort($countries, function ($a, $b) { return strcmp($a['name'], $b['name']); });
            return $countries;
        }

        \Log::warning('TigerSMS countries fetch failed', [
            'status' => $response->status(),
            'body_sample' => substr($response->body(), 0, 1000)
        ]);
        return [];
    }

    private function getTigerSmsServices(array $config, string $country): array
    {
        // More reliable approach: fetch full price map then select by numeric country id
        $mapUrl = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getPrices';
        $response = $this->httpClient->get($mapUrl);
        \Log::info('TigerSMS getPrices (services map) HTTP', [
            'url' => $this->sanitizeUrl($mapUrl),
            'status' => $response->status(),
            'body_sample' => substr($response->body(), 0, 1000),
        ]);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        if (!is_array($data)) {
            $decoded = json_decode($response->body(), true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $countryKey = (string)$country;
        $services = [];
        $servicesMap = $data[$countryKey] ?? null;
        if (!is_array($servicesMap)) {
            \Log::info('TigerSMS services map missing country', ['country' => $countryKey]);
            return [];
        }

        foreach ($servicesMap as $serviceCode => $serviceData) {
            $cost = null;
            $count = 0;
            if (is_array($serviceData)) {
                if (isset($serviceData['cost']) || isset($serviceData['price'])) {
                    $cost = (float)($serviceData['cost'] ?? $serviceData['price']);
                    $count = (int)($serviceData['count'] ?? $serviceData['available'] ?? $serviceData['phones'] ?? 0);
                } else {
                    foreach ($serviceData as $variant) {
                        if (is_array($variant)) {
                            $vcost = isset($variant['cost']) ? (float)$variant['cost'] : (isset($variant['price']) ? (float)$variant['price'] : (isset($variant['min_price']) ? (float)$variant['min_price'] : null));
                            $vcount = (int)($variant['count'] ?? $variant['available'] ?? $variant['phones'] ?? 0);
                            if ($vcost !== null) {
                                $cost = $cost === null ? $vcost : min($cost, $vcost);
                                $count = max($count, $vcount);
                            }
                        }
                    }
                }
            }

            if (!is_string($serviceCode)) {
                continue;
            }

            $services[] = [
                'name' => strtoupper($serviceCode),
                'service' => $serviceCode,
                'cost' => $cost !== null ? $cost : 0,
                'count' => $count,
            ];
        }

        \Log::info('TigerSMS parsed services summary', [
            'country' => $countryKey,
            'count' => count($services)
        ]);

        usort($services, function ($a, $b) { return ($a['cost'] <=> $b['cost']); });
        return $services;
    }

    private function sanitizeUrl(string $url): string
    {
        // Mask api_key value in logs
        return preg_replace('/(api_key=)[^&]*/i', '$1***', $url) ?? $url;
    }

    private function createTigerSmsOrder(array $config, string $country, string $service): array
    {
        // Tiger SMS handler API: action=getNumber&service=XXX&country=YYY
        $url = $config['api_url']
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getNumber'
            . '&service=' . urlencode($service)
            . '&country=' . urlencode($country);

        $response = $this->httpClient->get($url);
        $body = trim($response->body());
        \Log::info('TigerSMS getNumber HTTP', [
            'url' => $this->sanitizeUrl($url),
            'status' => $response->status(),
            'body_sample' => substr($body, 0, 300),
        ]);

        if ($response->successful() && $body) {
            // Expected: ACCESS_NUMBER:ID:PHONE or similar
            if (stripos($body, 'ACCESS_NUMBER') === 0) {
                $parts = explode(':', $body);
                $orderId = $parts[1] ?? null;
                $phone = $parts[2] ?? null;
                if ($orderId && $phone) {
                    return [
                        'order_id' => $orderId,
                        'phone_number' => $phone,
                        'cost' => 0, // Cost unknown here; UI shows estimated cost from services list
                        'status' => 'active',
                        'expires_at' => now()->addMinutes(20)
                    ];
                }
            }

            \Log::error('TigerSMS getNumber unexpected response', [
                'url' => $this->sanitizeUrl($url),
                'status' => $response->status(),
                'body' => $body,
            ]);
            throw new Exception('Tiger SMS getNumber unexpected response: ' . $body);
        }

        \Log::error('TigerSMS getNumber HTTP failure', [
            'url' => $this->sanitizeUrl($url),
            'status' => $response->status(),
            'body_sample' => substr($body, 0, 300),
        ]);
        throw new Exception('Failed to create Tiger SMS order: HTTP ' . $response->status());
    }

    private function getTigerSmsCode(array $config, string $orderId): ?string
    {
        // Tiger SMS handler API: action=getStatus&id=ORDER_ID
        $url = $config['api_url']
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=getStatus'
            . '&id=' . urlencode($orderId);

        $response = $this->httpClient->get($url);
        $body = trim($response->body());

        if ($response->successful() && $body) {
            if (stripos($body, 'STATUS_OK') === 0) {
                $parts = explode(':', $body, 2);
                return isset($parts[1]) ? trim($parts[1]) : null;
            }
            if (stripos($body, 'STATUS_WAIT') === 0) {
                return null;
            }
        }

        return null;
    }

    private function cancelTigerSmsOrder(array $config, string $orderId): bool
    {
        // Tiger SMS handler API: action=setStatus&id=ORDER_ID&status=8 (cancel)
        $url = $config['api_url']
            . '?api_key=' . urlencode((string)($config['api_key'] ?? ''))
            . '&action=setStatus'
            . '&id=' . urlencode($orderId)
            . '&status=8';

        $response = $this->httpClient->get($url);
        $body = trim($response->body());
        if ($response->successful()) {
            return stripos($body, 'ACCESS_CANCEL') === 0
                || stripos($body, 'STATUS_CANCEL') === 0
                || stripos($body, 'ACCESS') === 0;
        }
        return false;
    }

    private function getTigerSmsBalance(array $config): float
    {
        // Tiger SMS handler API: action=getBalance returns text ACCESS_BALANCE:amount
        $url = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getBalance';
        $response = $this->httpClient->get($url);
        $body = trim($response->body());
        if ($response->successful() && stripos($body, 'ACCESS_BALANCE') === 0) {
            $parts = explode(':', $body, 2);
            return isset($parts[1]) ? (float)$parts[1] : 0.0;
        }
        return 0.0;
    }
}
