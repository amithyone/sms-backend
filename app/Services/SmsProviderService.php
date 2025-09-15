<?php

namespace App\Services;

use App\Models\SmsService;
use App\Models\SmsOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use App\Services\Sms\Providers\TigerSmsProvider;
use App\Services\Sms\Providers\FiveSimProvider;
use App\Services\Sms\Providers\DassyProvider;
use App\Services\Sms\Providers\TextVerifiedProvider;

class SmsProviderService
{
    private $httpClient;
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
        // Map provider keys to concrete implementations
        $this->providers = [
            SmsService::PROVIDER_TIGER_SMS => new TigerSmsProvider(),
            SmsService::PROVIDER_5SIM => new FiveSimProvider(),
            SmsService::PROVIDER_DASSY => new DassyProvider(),
            SmsService::PROVIDER_TEXTVERIFIED => new TextVerifiedProvider(),
        ];
    }

    /**
     * Get available countries from SMS provider
     */
    public function getCountries(SmsService $smsService): array
    {
        try {
            $config = $smsService->getApiConfig();
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->getCountries($smsService);
            }
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimCountries($config);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassyCountries($config);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsCountries($config);
                case SmsService::PROVIDER_TEXTVERIFIED:
                    return $this->getTextVerifiedCountries($config);
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
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->getServices($smsService, $country);
            }
            
            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimServices($config, $country);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassyServices($config, $country);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsServices($config, $country);
                case SmsService::PROVIDER_TEXTVERIFIED:
                    return $this->getTextVerifiedServices($config, $country);
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
            
            Log::info("SmsProviderService: Creating order", [
                'provider' => $smsService->provider,
                'provider_name' => $smsService->name,
                'country' => $country,
                'service' => $service,
                'has_api_key' => !empty($config['api_key'] ?? ''),
                'has_username' => !empty($config['username'] ?? '')
            ]);
            
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->createOrder($smsService, $country, $service);
            }

            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    Log::info("Calling 5Sim API");
                    return $this->create5SimOrder($config, $country, $service);
                case SmsService::PROVIDER_DASSY:
                    Log::info("Calling Dassy API");
                    return $this->createDassyOrder($config, $country, $service);
                case SmsService::PROVIDER_TIGER_SMS:
                    Log::info("Calling Tiger SMS API");
                    return $this->createTigerSmsOrder($config, $country, $service);
                case SmsService::PROVIDER_TEXTVERIFIED:
                    Log::info("Calling TextVerified API");
                    return $this->createTextVerifiedOrder($config, $country, $service);
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
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->getSmsCode($smsService, $orderId);
            }

            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->get5SimSmsCode($config, $orderId);
                case SmsService::PROVIDER_DASSY:
                    return $this->getDassySmsCode($config, $orderId);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->getTigerSmsCode($config, $orderId);
                case SmsService::PROVIDER_TEXTVERIFIED:
                    return $this->getTextVerifiedSmsCode($config, $orderId);
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
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->cancelOrder($smsService, $orderId);
            }

            switch ($smsService->provider) {
                case SmsService::PROVIDER_5SIM:
                    return $this->cancel5SimOrder($config, $orderId);
                case SmsService::PROVIDER_DASSY:
                    return $this->cancelDassyOrder($config, $orderId);
                case SmsService::PROVIDER_TIGER_SMS:
                    return $this->cancelTigerSmsOrder($config, $orderId);
                case SmsService::PROVIDER_TEXTVERIFIED:
                    return $this->cancelTextVerifiedOrder($config, $orderId);
                default:
                    throw new Exception("Unsupported SMS provider: {$smsService->provider}");
            }
        } catch (Exception $e) {
            Log::error("Error cancelling order with {$smsService->provider}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Debug: probe a provider and return raw samples for inspection.
     */
    public function debugProbe(SmsService $smsService, ?string $country, ?string $service): array
    {
        $config = $smsService->getApiConfig();
        $provider = $smsService->provider;
        $samples = [ 'provider' => $provider ];

        try {
            switch ($provider) {
                case SmsService::PROVIDER_5SIM: {
                    $pricesUrl = ($config['api_url'] ?: 'http://api1.5sim.net/stubs/handler_api.php')
                        . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
                    $resp = $this->httpClient->get($pricesUrl);
                    $samples['prices_status'] = $resp->status();
                    $samples['prices_sample'] = substr($resp->body(), 0, 800);
                    break;
                }
                case SmsService::PROVIDER_DASSY: {
                    // DaisySMS
                    $pricesUrl = ($config['api_url'] ?: 'https://daisysms.com/stubs/handler_api.php')
                        . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
                    $resp = $this->httpClient->get($pricesUrl);
                    $samples['prices_status'] = $resp->status();
                    $samples['prices_sample'] = substr($resp->body(), 0, 800);
                    if ($country && $service) {
                        $getNumberUrl = ($config['api_url'] ?: 'https://daisysms.com/stubs/handler_api.php')
                            . '?api_key=' . urlencode($config['api_key'])
                            . '&action=getNumber&service=' . urlencode($service) . '&country=' . urlencode($country);
                        $r2 = $this->httpClient->get($getNumberUrl);
                        $samples['get_number_status'] = $r2->status();
                        $samples['get_number_sample'] = substr($r2->body(), 0, 300);
                    }
                    break;
                }
                case SmsService::PROVIDER_TIGER_SMS: {
                    $pricesUrl = ($config['api_url'] ?: 'https://api.tiger-sms.com/stubs/handler_api.php')
                        . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
                    $resp = $this->httpClient->get($pricesUrl);
                    $samples['prices_status'] = $resp->status();
                    $samples['prices_sample'] = substr($resp->body(), 0, 800);
                    break;
                }
                case SmsService::PROVIDER_TEXTVERIFIED: {
                    // Auth then services
                    try {
                        $authUrl = 'https://www.textverified.com/api/pub/v2/auth';
                        $r = $this->httpClient->post($authUrl, [ 'headers' => [
                            'X-API-KEY' => $config['api_key'] ?? '',
                            'X-API-USERNAME' => $config['settings']['username'] ?? '',
                        ]]);
                        $samples['auth_status'] = $r->status();
                        $samples['auth_sample'] = substr($r->body(), 0, 400);
                    } catch (\Throwable $e) {
                        $samples['auth_error'] = $e->getMessage();
                    }
                    try {
                        $svcUrl = 'https://www.textverified.com/api/pub/v2/services?numberType=mobile&reservationType=verification';
                        $r2 = $this->httpClient->get($svcUrl, [ 'headers' => [
                            'X-API-KEY' => $config['api_key'] ?? '',
                            'X-API-USERNAME' => $config['settings']['username'] ?? '',
                        ]]);
                        $samples['services_status'] = $r2->status();
                        $samples['services_sample'] = substr($r2->body(), 0, 800);
                    } catch (\Throwable $e) {
                        $samples['services_error'] = $e->getMessage();
                    }
                    break;
                }
            }
        } catch (\Throwable $e) {
            $samples['error'] = $e->getMessage();
        }

        return $samples;
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
                case SmsService::PROVIDER_TEXTVERIFIED:
                    return $this->getTextVerifiedNotifications($config, $lang);
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
            // Delegate to provider class if available
            if (isset($this->providers[$smsService->provider])) {
                return $this->providers[$smsService->provider]->getBalance($smsService);
            }

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

            case SmsService::PROVIDER_DASSY:
                // DaisySMS: use service-first map via action=getPricesVerification
                $url = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getPricesVerification';
                $resp = $this->httpClient->get($url);
                if (!$resp->successful()) return [];
                $data = $resp->json();
                if (!is_array($data)) {
                    $decoded = json_decode($resp->body(), true);
                    $data = is_array($decoded) ? $decoded : [];
                }
                $serviceKey = strtolower($serviceCodeOrName);
                $block = $data[$serviceKey] ?? null; // expected shape: service => countryId => entry
                if (!is_array($block)) {
                    // try exact match over keys case-insensitively
                    foreach ($data as $svc => $countries) {
                        if (is_string($svc) && strtolower($svc) === $serviceKey) { $block = $countries; break; }
                    }
                }
                if (!is_array($block)) return [];
                $rows = [];
                foreach ($block as $countryId => $entry) {
                    if (!is_string($countryId) && !is_int($countryId)) continue;
                    $cost = 0.0; $count = 0;
                    if (is_array($entry)) {
                        $cost = isset($entry['cost']) ? (float)$entry['cost'] : (isset($entry['price']) ? (float)$entry['price'] : 0.0);
                        $count = (int)($entry['count'] ?? $entry['available'] ?? $entry['phones'] ?? 0);
                    }
                    $rows[] = [
                        'country_id' => (string)$countryId,
                        'country_name' => DB::table('sms_countries')->where('provider','dassy')->where('country_id',(string)$countryId)->value('name') ?? ('Country ' . $countryId),
                        'cost' => $cost,
                        'count' => $count,
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
        Log::info('5SIM getNumbersStatus HTTP', [
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
        Log::info('5SIM getNumber HTTP', [
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
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data)) {
                $countries = [];
                foreach ($data as $countryCode => $services) {
                    if (is_array($services) && !empty($services)) {
                        $countries[] = [
                            'code' => (string)$countryCode,
                            'name' => $this->getCountryNameByCode($countryCode)
                        ];
                    }
                }
                return $countries;
            }
        }
        
        Log::error('DaisySMS getCountries failed', [
            'url' => $url,
            'status' => $response->status(),
            'body_sample' => substr($response->body(), 0, 300)
        ]);
        
        return [];
    }

    private function getDassyServices(array $config, string $country): array
    {
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data) && isset($data[$country])) {
                $services = [];
                foreach ($data[$country] as $serviceCode => $serviceData) {
                    if (is_array($serviceData) && isset($serviceData['cost'])) {
                        $services[] = [
                            'service' => $serviceCode,
                            'name' => $this->getServiceNameByCode($serviceCode),
                            'cost' => (float)$serviceData['cost'],
                            'count' => (int)($serviceData['count'] ?? 0)
                        ];
                    }
                }
                return $services;
            }
        }
        
        Log::error('DaisySMS getServices failed', [
            'url' => $url,
            'country' => $country,
            'status' => $response->status(),
            'body_sample' => substr($response->body(), 0, 300)
        ]);
        
        return [];
    }

    private function createDassyOrder(array $config, string $country, string $service): array
    {
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getNumber&service=' . urlencode($service) . '&country=' . urlencode($country);
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $body = trim($response->body());
            
            // Parse response: ACCESS_NUMBER:order_id:phone_number
            if (strpos($body, 'ACCESS_NUMBER:') === 0) {
                $parts = explode(':', $body, 3);
                if (count($parts) >= 3) {
                    return [
                        'order_id' => $parts[1],
                        'phone_number' => $parts[2],
                        'cost' => 0, // Cost not provided in response, will be updated later
                        'status' => 'active',
                        'expires_at' => now()->addMinutes(15) // Default 15 minutes
                    ];
                }
            }
            
            // Handle error responses
            if ($body === 'NO_NUMBERS') {
                throw new Exception('No numbers available for this service');
            } elseif ($body === 'NO_MONEY') {
                throw new Exception('Insufficient balance');
            } elseif ($body === 'TOO_MANY_ACTIVE_RENTALS') {
                throw new Exception('Too many active rentals');
            }
        }
        
        Log::error('DaisySMS createOrder failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        
        throw new Exception('Failed to create DaisySMS order: ' . $response->body());
    }

    private function getDassySmsCode(array $config, string $orderId): ?string
    {
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getStatus&id=' . urlencode($orderId);
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $body = trim($response->body());
            
            // Parse response: STATUS_OK:code
            if (strpos($body, 'STATUS_OK:') === 0) {
                $parts = explode(':', $body, 2);
                if (count($parts) >= 2) {
                    return $parts[1];
                }
            }
            
            // Other status responses
            if ($body === 'STATUS_WAIT_CODE') {
                return null; // Still waiting
            } elseif ($body === 'STATUS_CANCEL') {
                return null; // Cancelled
            }
        }
        
        return null;
    }

    private function cancelDassyOrder(array $config, string $orderId): bool
    {
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=setStatus&id=' . urlencode($orderId) . '&status=8';
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $body = trim($response->body());
            return $body === 'ACCESS_CANCEL';
        }
        
        return false;
    }

    private function getDassyBalance(array $config): float
    {
        // DaisySMS uses SMS-activate compatible API
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getBalance';
        $response = $this->httpClient->get($url);
        
        if ($response->successful()) {
            $body = trim($response->body());
            
            // Parse response: ACCESS_BALANCE:amount
            if (strpos($body, 'ACCESS_BALANCE:') === 0) {
                $parts = explode(':', $body, 2);
                if (count($parts) >= 2) {
                    return (float)$parts[1];
                }
            }
        }
        
        return 0.0;
    }

    // Tiger SMS API Methods
    private function getTigerSmsCountries(array $config): array
    {
        // Tiger SMS handler API: action=getCountries returns JSON
        $url = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getCountries';
        $response = $this->httpClient->get($url);
        Log::info('TigerSMS getCountries HTTP', [
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
        Log::info('TigerSMS getPrices (countries fallback) HTTP', [
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

        Log::warning('TigerSMS countries fetch failed', [
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
        Log::info('TigerSMS getPrices (services map) HTTP', [
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
            Log::info('TigerSMS services map missing country', ['country' => $countryKey]);
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

        Log::info('TigerSMS parsed services summary', [
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
        Log::info('TigerSMS getNumber HTTP', [
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

            Log::error('TigerSMS getNumber unexpected response', [
                'url' => $this->sanitizeUrl($url),
                'status' => $response->status(),
                'body' => $body,
            ]);
            throw new Exception('Tiger SMS getNumber unexpected response: ' . $body);
        }

        Log::error('TigerSMS getNumber HTTP failure', [
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

    // TextVerified API Implementation
    private function getTextVerifiedCountries(array $config): array
    {
        // TextVerified is effectively US-only for virtual numbers.
        // Their v2 public API does not expose a countries endpoint compatible with this call
        // and previous attempts to use https://www.textverified.com/api/v2/countries returned 404.
        // To avoid unnecessary HTTP calls and errors, return the supported country statically.
        return [
            [ 'code' => 'US', 'name' => 'United States' ],
        ];
    }

    private function getTextVerifiedServices(array $config, string $country): array
    {
        // TextVerified API v2: GET /services with parameters
        $bearerToken = $this->getTextVerifiedBearerToken($config);
        $url = 'https://www.textverified.com/api/pub/v2/services';
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json'
        ];

        $params = [
            'numberType' => 'mobile',
            'reservationType' => 'verification'
        ];

        $response = $this->httpClient->get($url . '?' . http_build_query($params), [
            'headers' => $headers
        ]);
        
        if (!$response->successful()) {
            Log::error('TextVerified getServices HTTP', [
                'url' => $url,
                'status' => $response->status(),
                'body_sample' => substr($response->body(), 0, 300),
            ]);
            return [];
        }

        $data = json_decode($response->body(), true);
        if (!is_array($data)) {
            return [];
        }

        $services = [];
        foreach ($data as $service) {
            if (isset($service['serviceName'])) {
                $services[] = [
                    'id' => $service['serviceName'], // Use serviceName as ID for TextVerified
                    'name' => $service['serviceName'],
                    'cost' => 0, // TextVerified doesn't provide cost in service list
                    'description' => $service['capability'] ?? 'sms',
                    'available' => true
                ];
            }
        }

        return $services;
    }

    private function createTextVerifiedOrder(array $config, string $country, string $service): array
    {
        // TextVerified API v2: First get bearer token, then create verification
        $bearerToken = $this->getTextVerifiedBearerToken($config);
        
        // Create verification
        $url = 'https://www.textverified.com/api/pub/v2/verifications';
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json'
        ];

        $payload = [
            'serviceName' => $service, // TextVerified uses serviceName from service list
            'capability' => 'sms' // sms, voice, smsAndVoiceCombo
        ];

        Log::info('TextVerified createOrder request', [
            'url' => $url,
            'payload' => $payload
        ]);

        $response = $this->httpClient->post($url, [
            'headers' => $headers,
            'json' => $payload
        ]);

        if (!$response->successful()) {
            Log::error('TextVerified createOrder HTTP', [
                'url' => $url,
                'status' => $response->status(),
                'body_sample' => substr($response->body(), 0, 300),
            ]);
            throw new Exception('Failed to create TextVerified order: HTTP ' . $response->status());
        }

        $data = json_decode($response->body(), true);
        if (!is_array($data) || !isset($data['href'])) {
            Log::error('TextVerified createOrder invalid response', [
                'response' => $data
            ]);
            throw new Exception('Invalid response from TextVerified API');
        }

        // Get verification details to get phone number
        $verificationDetails = $this->getTextVerifiedVerificationDetails($bearerToken, $data['href']);

        return [
            'order_id' => $data['href'], // Use href as order_id for TextVerified
            'phone_number' => $verificationDetails['phoneNumber'] ?? '',
            'cost' => (float)($verificationDetails['cost'] ?? 0),
            'status' => $verificationDetails['state'] ?? 'pending',
            'expires_at' => $verificationDetails['expiresAt'] ?? null
        ];
    }

    private function getTextVerifiedBearerToken(array $config): string
    {
        // TextVerified API v2: POST /auth to get bearer token
        $url = 'https://www.textverified.com/api/pub/v2/auth';
        $headers = [
            'X-API-KEY' => $config['api_key'] ?? '',
            'X-API-USERNAME' => $config['settings']['username'] ?? '',
            'Content-Type' => 'application/json'
        ];

        Log::info('TextVerified getBearerToken request', [
            'url' => $url,
            'has_api_key' => !empty($config['api_key']),
            'has_username' => !empty($config['settings']['username'])
        ]);

        $response = $this->httpClient->post($url, [
            'headers' => $headers
        ]);

        if (!$response->successful()) {
            Log::error('TextVerified getBearerToken HTTP', [
                'url' => $url,
                'status' => $response->status(),
                'body_sample' => substr($response->body(), 0, 300),
            ]);
            throw new Exception('Failed to get TextVerified bearer token: HTTP ' . $response->status());
        }

        $data = json_decode($response->body(), true);
        if (!is_array($data) || !isset($data['token'])) {
            Log::error('TextVerified getBearerToken invalid response', [
                'response' => $data
            ]);
            throw new Exception('Invalid response from TextVerified auth API');
        }

        return $data['token'];
    }

    private function getTextVerifiedVerificationDetails(string $bearerToken, string $href): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json'
        ];

        $response = $this->httpClient->get($href, [
            'headers' => $headers
        ]);

        if (!$response->successful()) {
            Log::error('TextVerified getVerificationDetails HTTP', [
                'url' => $href,
                'status' => $response->status(),
                'body_sample' => substr($response->body(), 0, 300),
            ]);
            throw new Exception('Failed to get TextVerified verification details: HTTP ' . $response->status());
        }

        $data = json_decode($response->body(), true);
        if (!is_array($data) || !isset($data['data'])) {
            throw new Exception('Invalid response from TextVerified verification details API');
        }

        return $data['data'];
    }

    private function getTextVerifiedSmsCode(array $config, string $orderId): ?string
    {
        // TextVerified API v2: GET verification details using href
        $bearerToken = $this->getTextVerifiedBearerToken($config);
        $verificationDetails = $this->getTextVerifiedVerificationDetails($bearerToken, $orderId);
        
        // Check if verification is completed
        if (($verificationDetails['state'] ?? '') === 'verificationCompleted') {
            return $verificationDetails['code'] ?? null;
        }
        
        return null;
    }

    private function cancelTextVerifiedOrder(array $config, string $orderId): bool
    {
        // TextVerified API v2: DELETE verification using href
        $bearerToken = $this->getTextVerifiedBearerToken($config);
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json'
        ];

        $response = $this->httpClient->delete($orderId, [
            'headers' => $headers
        ]);

        return $response->successful();
    }

    private function getTextVerifiedNotifications(array $config, string $lang = 'en'): array
    {
        // TextVerified doesn't have a specific notifications endpoint
        // Return empty array for now
        return [];
    }

    /**
     * Get country name by code
     */
    private function getCountryNameByCode(string $code): string
    {
        $countries = [
            '187' => 'United States',
            '1' => 'United States',
            '7' => 'Russia',
            '44' => 'United Kingdom',
            '49' => 'Germany',
            '33' => 'France',
            '39' => 'Italy',
            '34' => 'Spain',
            '31' => 'Netherlands',
            '32' => 'Belgium',
            '41' => 'Switzerland',
            '43' => 'Austria',
            '45' => 'Denmark',
            '46' => 'Sweden',
            '47' => 'Norway',
            '48' => 'Poland',
            '420' => 'Czech Republic',
            '421' => 'Slovakia',
            '36' => 'Hungary',
            '40' => 'Romania',
            '359' => 'Bulgaria',
            '385' => 'Croatia',
            '386' => 'Slovenia',
            '372' => 'Estonia',
            '371' => 'Latvia',
            '370' => 'Lithuania',
            '30' => 'Greece',
            '351' => 'Portugal',
            '353' => 'Ireland',
            '358' => 'Finland',
            '372' => 'Estonia',
            '371' => 'Latvia',
            '370' => 'Lithuania',
            '90' => 'Turkey',
            '380' => 'Ukraine',
            '375' => 'Belarus',
            '7' => 'Kazakhstan',
            '998' => 'Uzbekistan',
            '996' => 'Kyrgyzstan',
            '992' => 'Tajikistan',
            '993' => 'Turkmenistan',
            '374' => 'Armenia',
            '995' => 'Georgia',
            '994' => 'Azerbaijan',
            '81' => 'Japan',
            '82' => 'South Korea',
            '86' => 'China',
            '852' => 'Hong Kong',
            '853' => 'Macau',
            '886' => 'Taiwan',
            '65' => 'Singapore',
            '60' => 'Malaysia',
            '66' => 'Thailand',
            '84' => 'Vietnam',
            '855' => 'Cambodia',
            '856' => 'Laos',
            '95' => 'Myanmar',
            '880' => 'Bangladesh',
            '91' => 'India',
            '92' => 'Pakistan',
            '93' => 'Afghanistan',
            '94' => 'Sri Lanka',
            '977' => 'Nepal',
            '975' => 'Bhutan',
            '960' => 'Maldives',
            '98' => 'Iran',
            '964' => 'Iraq',
            '963' => 'Syria',
            '961' => 'Lebanon',
            '962' => 'Jordan',
            '972' => 'Israel',
            '970' => 'Palestine',
            '966' => 'Saudi Arabia',
            '965' => 'Kuwait',
            '973' => 'Bahrain',
            '974' => 'Qatar',
            '971' => 'UAE',
            '968' => 'Oman',
            '967' => 'Yemen',
            '20' => 'Egypt',
            '218' => 'Libya',
            '216' => 'Tunisia',
            '213' => 'Algeria',
            '212' => 'Morocco',
            '222' => 'Mauritania',
            '220' => 'Gambia',
            '221' => 'Senegal',
            '223' => 'Mali',
            '224' => 'Guinea',
            '225' => 'Ivory Coast',
            '226' => 'Burkina Faso',
            '227' => 'Niger',
            '228' => 'Togo',
            '229' => 'Benin',
            '230' => 'Mauritius',
            '231' => 'Liberia',
            '232' => 'Sierra Leone',
            '233' => 'Ghana',
            '234' => 'Nigeria',
            '235' => 'Chad',
            '236' => 'Central African Republic',
            '237' => 'Cameroon',
            '238' => 'Cape Verde',
            '239' => 'Sao Tome and Principe',
            '240' => 'Equatorial Guinea',
            '241' => 'Gabon',
            '242' => 'Republic of the Congo',
            '243' => 'Democratic Republic of the Congo',
            '244' => 'Angola',
            '245' => 'Guinea-Bissau',
            '246' => 'British Indian Ocean Territory',
            '248' => 'Seychelles',
            '249' => 'Sudan',
            '250' => 'Rwanda',
            '251' => 'Ethiopia',
            '252' => 'Somalia',
            '253' => 'Djibouti',
            '254' => 'Kenya',
            '255' => 'Tanzania',
            '256' => 'Uganda',
            '257' => 'Burundi',
            '258' => 'Mozambique',
            '260' => 'Zambia',
            '261' => 'Madagascar',
            '262' => 'Reunion',
            '263' => 'Zimbabwe',
            '264' => 'Namibia',
            '265' => 'Malawi',
            '266' => 'Lesotho',
            '267' => 'Botswana',
            '268' => 'Swaziland',
            '269' => 'Comoros',
            '290' => 'Saint Helena',
            '291' => 'Eritrea',
            '297' => 'Aruba',
            '298' => 'Faroe Islands',
            '299' => 'Greenland',
            '350' => 'Gibraltar',
            '352' => 'Luxembourg',
            '354' => 'Iceland',
            '356' => 'Malta',
            '357' => 'Cyprus',
            '377' => 'Monaco',
            '378' => 'San Marino',
            '379' => 'Vatican',
            '380' => 'Ukraine',
            '381' => 'Serbia',
            '382' => 'Montenegro',
            '383' => 'Kosovo',
            '385' => 'Croatia',
            '386' => 'Slovenia',
            '387' => 'Bosnia and Herzegovina',
            '389' => 'North Macedonia',
            '590' => 'Guadeloupe',
            '591' => 'Bolivia',
            '592' => 'Guyana',
            '593' => 'Ecuador',
            '594' => 'French Guiana',
            '595' => 'Paraguay',
            '596' => 'Martinique',
            '597' => 'Suriname',
            '598' => 'Uruguay',
            '599' => 'Netherlands Antilles',
            '670' => 'East Timor',
            '672' => 'Antarctica',
            '673' => 'Brunei',
            '674' => 'Nauru',
            '675' => 'Papua New Guinea',
            '676' => 'Tonga',
            '677' => 'Solomon Islands',
            '678' => 'Vanuatu',
            '679' => 'Fiji',
            '680' => 'Palau',
            '681' => 'Wallis and Futuna',
            '682' => 'Cook Islands',
            '683' => 'Niue',
            '684' => 'American Samoa',
            '685' => 'Samoa',
            '686' => 'Kiribati',
            '687' => 'New Caledonia',
            '688' => 'Tuvalu',
            '689' => 'French Polynesia',
            '690' => 'Tokelau',
            '691' => 'Micronesia',
            '692' => 'Marshall Islands',
            '850' => 'North Korea',
            '852' => 'Hong Kong',
            '853' => 'Macau',
            '855' => 'Cambodia',
            '856' => 'Laos',
            '880' => 'Bangladesh',
            '886' => 'Taiwan',
            '960' => 'Maldives',
            '961' => 'Lebanon',
            '962' => 'Jordan',
            '963' => 'Syria',
            '964' => 'Iraq',
            '965' => 'Kuwait',
            '966' => 'Saudi Arabia',
            '967' => 'Yemen',
            '968' => 'Oman',
            '970' => 'Palestine',
            '971' => 'UAE',
            '972' => 'Israel',
            '973' => 'Bahrain',
            '974' => 'Qatar',
            '975' => 'Bhutan',
            '976' => 'Mongolia',
            '977' => 'Nepal',
            '992' => 'Tajikistan',
            '993' => 'Turkmenistan',
            '994' => 'Azerbaijan',
            '995' => 'Georgia',
            '996' => 'Kyrgyzstan',
            '998' => 'Uzbekistan',
        ];

        return $countries[$code] ?? "Country {$code}";
    }

    /**
     * Get service name by code
     */
    private function getServiceNameByCode(string $code): string
    {
        $services = [
            'wa' => 'WhatsApp',
            'go' => 'Google',
            'fb' => 'Facebook',
            'ig' => 'Instagram',
            'tw' => 'Twitter',
            'tg' => 'Telegram',
            'ds' => 'Discord',
            'sn' => 'Snapchat',
            'vk' => 'VKontakte',
            'ok' => 'Odnoklassniki',
            'av' => 'Avito',
            'am' => 'Amazon',
            'ap' => 'Apple',
            'bb' => 'Badoo',
            'bl' => 'Blizzard',
            'bz' => 'BuzzFeed',
            'ca' => 'Carousell',
            'cb' => 'Coinbase',
            'cm' => 'Comcast',
            'co' => 'Correos',
            'cr' => 'Craigslist',
            'cs' => 'CSGO',
            'cu' => 'Cupid',
            'dc' => 'Discord',
            'dh' => 'DoorDash',
            'di' => 'DiDi',
            'dr' => 'Drom',
            'dt' => 'Dating',
            'eb' => 'eBay',
            'et' => 'Etsy',
            'ew' => 'EWallet',
            'ex' => 'Express',
            'fa' => 'Facebook',
            'fd' => 'Foodpanda',
            'fi' => 'Fiverr',
            'fl' => 'Flickr',
            'fm' => 'Fotostrana',
            'fo' => 'Foody',
            'fr' => 'Fiverr',
            'ft' => 'Fotostrana',
            'ga' => 'Garena',
            'gb' => 'Grab',
            'gd' => 'Grab',
            'ge' => 'Getir',
            'gl' => 'Glovo',
            'gm' => 'Gmail',
            'go' => 'Google',
            'gp' => 'Google Play',
            'gr' => 'Grab',
            'gt' => 'Gett',
            'gu' => 'Grab',
            'gw' => 'Grab',
            'gy' => 'Grab',
            'hb' => 'HBO',
            'he' => 'Here',
            'hi' => 'Hike',
            'ho' => 'Hotmail',
            'hs' => 'HSBC',
            'hu' => 'Hulu',
            'hw' => 'Huawei',
            'hy' => 'Hyundai',
            'ic' => 'ICQ',
            'id' => 'Instagram',
            'ig' => 'Instagram',
            'im' => 'Imo',
            'in' => 'Instagram',
            'io' => 'Instagram',
            'ip' => 'Instagram',
            'iq' => 'Instagram',
            'ir' => 'Instagram',
            'is' => 'Instagram',
            'it' => 'Instagram',
            'iu' => 'Instagram',
            'iv' => 'Instagram',
            'iw' => 'Instagram',
            'ix' => 'Instagram',
            'iy' => 'Instagram',
            'iz' => 'Instagram',
            'ja' => 'Jabber',
            'jb' => 'Jabber',
            'jc' => 'Jabber',
            'jd' => 'Jabber',
            'je' => 'Jabber',
            'jf' => 'Jabber',
            'jg' => 'Jabber',
            'jh' => 'Jabber',
            'ji' => 'Jabber',
            'jj' => 'Jabber',
            'jk' => 'Jabber',
            'jl' => 'Jabber',
            'jm' => 'Jabber',
            'jn' => 'Jabber',
            'jo' => 'Jabber',
            'jp' => 'Jabber',
            'jq' => 'Jabber',
            'jr' => 'Jabber',
            'js' => 'Jabber',
            'jt' => 'Jabber',
            'ju' => 'Jabber',
            'jv' => 'Jabber',
            'jw' => 'Jabber',
            'jx' => 'Jabber',
            'jy' => 'Jabber',
            'jz' => 'Jabber',
            'ka' => 'KakaoTalk',
            'kb' => 'KakaoTalk',
            'kc' => 'KakaoTalk',
            'kd' => 'KakaoTalk',
            'ke' => 'KakaoTalk',
            'kf' => 'KakaoTalk',
            'kg' => 'KakaoTalk',
            'kh' => 'KakaoTalk',
            'ki' => 'KakaoTalk',
            'kj' => 'KakaoTalk',
            'kk' => 'KakaoTalk',
            'kl' => 'KakaoTalk',
            'km' => 'KakaoTalk',
            'kn' => 'KakaoTalk',
            'ko' => 'KakaoTalk',
            'kp' => 'KakaoTalk',
            'kq' => 'KakaoTalk',
            'kr' => 'KakaoTalk',
            'ks' => 'KakaoTalk',
            'kt' => 'KakaoTalk',
            'ku' => 'KakaoTalk',
            'kv' => 'KakaoTalk',
            'kw' => 'KakaoTalk',
            'kx' => 'KakaoTalk',
            'ky' => 'KakaoTalk',
            'kz' => 'KakaoTalk',
            'la' => 'Line',
            'lb' => 'Line',
            'lc' => 'Line',
            'ld' => 'Line',
            'le' => 'Line',
            'lf' => 'Line',
            'lg' => 'Line',
            'lh' => 'Line',
            'li' => 'Line',
            'lj' => 'Line',
            'lk' => 'Line',
            'll' => 'Line',
            'lm' => 'Line',
            'ln' => 'Line',
            'lo' => 'Line',
            'lp' => 'Line',
            'lq' => 'Line',
            'lr' => 'Line',
            'ls' => 'Line',
            'lt' => 'Line',
            'lu' => 'Line',
            'lv' => 'Line',
            'lw' => 'Line',
            'lx' => 'Line',
            'ly' => 'Line',
            'lz' => 'Line',
            'ma' => 'Mail.ru',
            'mb' => 'Mail.ru',
            'mc' => 'Mail.ru',
            'md' => 'Mail.ru',
            'me' => 'Mail.ru',
            'mf' => 'Mail.ru',
            'mg' => 'Mail.ru',
            'mh' => 'Mail.ru',
            'mi' => 'Mail.ru',
            'mj' => 'Mail.ru',
            'mk' => 'Mail.ru',
            'ml' => 'Mail.ru',
            'mm' => 'Mail.ru',
            'mn' => 'Mail.ru',
            'mo' => 'Mail.ru',
            'mp' => 'Mail.ru',
            'mq' => 'Mail.ru',
            'mr' => 'Mail.ru',
            'ms' => 'Mail.ru',
            'mt' => 'Mail.ru',
            'mu' => 'Mail.ru',
            'mv' => 'Mail.ru',
            'mw' => 'Mail.ru',
            'mx' => 'Mail.ru',
            'my' => 'Mail.ru',
            'mz' => 'Mail.ru',
            'na' => 'Naver',
            'nb' => 'Naver',
            'nc' => 'Naver',
            'nd' => 'Naver',
            'ne' => 'Naver',
            'nf' => 'Naver',
            'ng' => 'Naver',
            'nh' => 'Naver',
            'ni' => 'Naver',
            'nj' => 'Naver',
            'nk' => 'Naver',
            'nl' => 'Naver',
            'nm' => 'Naver',
            'nn' => 'Naver',
            'no' => 'Naver',
            'np' => 'Naver',
            'nq' => 'Naver',
            'nr' => 'Naver',
            'ns' => 'Naver',
            'nt' => 'Naver',
            'nu' => 'Naver',
            'nv' => 'Naver',
            'nw' => 'Naver',
            'nx' => 'Naver',
            'ny' => 'Naver',
            'nz' => 'Naver',
            'oa' => 'Ola',
            'ob' => 'Ola',
            'oc' => 'Ola',
            'od' => 'Ola',
            'oe' => 'Ola',
            'of' => 'Ola',
            'og' => 'Ola',
            'oh' => 'Ola',
            'oi' => 'Ola',
            'oj' => 'Ola',
            'ok' => 'Ola',
            'ol' => 'Ola',
            'om' => 'Ola',
            'on' => 'Ola',
            'oo' => 'Ola',
            'op' => 'Ola',
            'oq' => 'Ola',
            'or' => 'Ola',
            'os' => 'Ola',
            'ot' => 'Ola',
            'ou' => 'Ola',
            'ov' => 'Ola',
            'ow' => 'Ola',
            'ox' => 'Ola',
            'oy' => 'Ola',
            'oz' => 'Ola',
            'pa' => 'PayPal',
            'pb' => 'PayPal',
            'pc' => 'PayPal',
            'pd' => 'PayPal',
            'pe' => 'PayPal',
            'pf' => 'PayPal',
            'pg' => 'PayPal',
            'ph' => 'PayPal',
            'pi' => 'PayPal',
            'pj' => 'PayPal',
            'pk' => 'PayPal',
            'pl' => 'PayPal',
            'pm' => 'PayPal',
            'pn' => 'PayPal',
            'po' => 'PayPal',
            'pp' => 'PayPal',
            'pq' => 'PayPal',
            'pr' => 'PayPal',
            'ps' => 'PayPal',
            'pt' => 'PayPal',
            'pu' => 'PayPal',
            'pv' => 'PayPal',
            'pw' => 'PayPal',
            'px' => 'PayPal',
            'py' => 'PayPal',
            'pz' => 'PayPal',
            'qa' => 'Qatar',
            'qb' => 'Qatar',
            'qc' => 'Qatar',
            'qd' => 'Qatar',
            'qe' => 'Qatar',
            'qf' => 'Qatar',
            'qg' => 'Qatar',
            'qh' => 'Qatar',
            'qi' => 'Qatar',
            'qj' => 'Qatar',
            'qk' => 'Qatar',
            'ql' => 'Qatar',
            'qm' => 'Qatar',
            'qn' => 'Qatar',
            'qo' => 'Qatar',
            'qp' => 'Qatar',
            'qq' => 'Qatar',
            'qr' => 'Qatar',
            'qs' => 'Qatar',
            'qt' => 'Qatar',
            'qu' => 'Qatar',
            'qv' => 'Qatar',
            'qw' => 'Qatar',
            'qx' => 'Qatar',
            'qy' => 'Qatar',
            'qz' => 'Qatar',
            'ra' => 'Rakuten',
            'rb' => 'Rakuten',
            'rc' => 'Rakuten',
            'rd' => 'Rakuten',
            're' => 'Rakuten',
            'rf' => 'Rakuten',
            'rg' => 'Rakuten',
            'rh' => 'Rakuten',
            'ri' => 'Rakuten',
            'rj' => 'Rakuten',
            'rk' => 'Rakuten',
            'rl' => 'Rakuten',
            'rm' => 'Rakuten',
            'rn' => 'Rakuten',
            'ro' => 'Rakuten',
            'rp' => 'Rakuten',
            'rq' => 'Rakuten',
            'rr' => 'Rakuten',
            'rs' => 'Rakuten',
            'rt' => 'Rakuten',
            'ru' => 'Rakuten',
            'rv' => 'Rakuten',
            'rw' => 'Rakuten',
            'rx' => 'Rakuten',
            'ry' => 'Rakuten',
            'rz' => 'Rakuten',
            'sa' => 'Samsung',
            'sb' => 'Samsung',
            'sc' => 'Samsung',
            'sd' => 'Samsung',
            'se' => 'Samsung',
            'sf' => 'Samsung',
            'sg' => 'Samsung',
            'sh' => 'Samsung',
            'si' => 'Samsung',
            'sj' => 'Samsung',
            'sk' => 'Samsung',
            'sl' => 'Samsung',
            'sm' => 'Samsung',
            'sn' => 'Samsung',
            'so' => 'Samsung',
            'sp' => 'Samsung',
            'sq' => 'Samsung',
            'sr' => 'Samsung',
            'ss' => 'Samsung',
            'st' => 'Samsung',
            'su' => 'Samsung',
            'sv' => 'Samsung',
            'sw' => 'Samsung',
            'sx' => 'Samsung',
            'sy' => 'Samsung',
            'sz' => 'Samsung',
            'ta' => 'Tango',
            'tb' => 'Tango',
            'tc' => 'Tango',
            'td' => 'Tango',
            'te' => 'Tango',
            'tf' => 'Tango',
            'tg' => 'Tango',
            'th' => 'Tango',
            'ti' => 'Tango',
            'tj' => 'Tango',
            'tk' => 'Tango',
            'tl' => 'Tango',
            'tm' => 'Tango',
            'tn' => 'Tango',
            'to' => 'Tango',
            'tp' => 'Tango',
            'tq' => 'Tango',
            'tr' => 'Tango',
            'ts' => 'Tango',
            'tt' => 'Tango',
            'tu' => 'Tango',
            'tv' => 'Tango',
            'tw' => 'Tango',
            'tx' => 'Tango',
            'ty' => 'Tango',
            'tz' => 'Tango',
            'ua' => 'Uber',
            'ub' => 'Uber',
            'uc' => 'Uber',
            'ud' => 'Uber',
            'ue' => 'Uber',
            'uf' => 'Uber',
            'ug' => 'Uber',
            'uh' => 'Uber',
            'ui' => 'Uber',
            'uj' => 'Uber',
            'uk' => 'Uber',
            'ul' => 'Uber',
            'um' => 'Uber',
            'un' => 'Uber',
            'uo' => 'Uber',
            'up' => 'Uber',
            'uq' => 'Uber',
            'ur' => 'Uber',
            'us' => 'Uber',
            'ut' => 'Uber',
            'uu' => 'Uber',
            'uv' => 'Uber',
            'uw' => 'Uber',
            'ux' => 'Uber',
            'uy' => 'Uber',
            'uz' => 'Uber',
            'va' => 'Viber',
            'vb' => 'Viber',
            'vc' => 'Viber',
            'vd' => 'Viber',
            've' => 'Viber',
            'vf' => 'Viber',
            'vg' => 'Viber',
            'vh' => 'Viber',
            'vi' => 'Viber',
            'vj' => 'Viber',
            'vk' => 'Viber',
            'vl' => 'Viber',
            'vm' => 'Viber',
            'vn' => 'Viber',
            'vo' => 'Viber',
            'vp' => 'Viber',
            'vq' => 'Viber',
            'vr' => 'Viber',
            'vs' => 'Viber',
            'vt' => 'Viber',
            'vu' => 'Viber',
            'vv' => 'Viber',
            'vw' => 'Viber',
            'vx' => 'Viber',
            'vy' => 'Viber',
            'vz' => 'Viber',
            'wa' => 'WhatsApp',
            'wb' => 'WhatsApp',
            'wc' => 'WhatsApp',
            'wd' => 'WhatsApp',
            'we' => 'WhatsApp',
            'wf' => 'WhatsApp',
            'wg' => 'WhatsApp',
            'wh' => 'WhatsApp',
            'wi' => 'WhatsApp',
            'wj' => 'WhatsApp',
            'wk' => 'WhatsApp',
            'wl' => 'WhatsApp',
            'wm' => 'WhatsApp',
            'wn' => 'WhatsApp',
            'wo' => 'WhatsApp',
            'wp' => 'WhatsApp',
            'wq' => 'WhatsApp',
            'wr' => 'WhatsApp',
            'ws' => 'WhatsApp',
            'wt' => 'WhatsApp',
            'wu' => 'WhatsApp',
            'wv' => 'WhatsApp',
            'ww' => 'WhatsApp',
            'wx' => 'WhatsApp',
            'wy' => 'WhatsApp',
            'wz' => 'WhatsApp',
            'xa' => 'Xbox',
            'xb' => 'Xbox',
            'xc' => 'Xbox',
            'xd' => 'Xbox',
            'xe' => 'Xbox',
            'xf' => 'Xbox',
            'xg' => 'Xbox',
            'xh' => 'Xbox',
            'xi' => 'Xbox',
            'xj' => 'Xbox',
            'xk' => 'Xbox',
            'xl' => 'Xbox',
            'xm' => 'Xbox',
            'xn' => 'Xbox',
            'xo' => 'Xbox',
            'xp' => 'Xbox',
            'xq' => 'Xbox',
            'xr' => 'Xbox',
            'xs' => 'Xbox',
            'xt' => 'Xbox',
            'xu' => 'Xbox',
            'xv' => 'Xbox',
            'xw' => 'Xbox',
            'xx' => 'Xbox',
            'xy' => 'Xbox',
            'xz' => 'Xbox',
            'ya' => 'Yahoo',
            'yb' => 'Yahoo',
            'yc' => 'Yahoo',
            'yd' => 'Yahoo',
            'ye' => 'Yahoo',
            'yf' => 'Yahoo',
            'yg' => 'Yahoo',
            'yh' => 'Yahoo',
            'yi' => 'Yahoo',
            'yj' => 'Yahoo',
            'yk' => 'Yahoo',
            'yl' => 'Yahoo',
            'ym' => 'Yahoo',
            'yn' => 'Yahoo',
            'yo' => 'Yahoo',
            'yp' => 'Yahoo',
            'yq' => 'Yahoo',
            'yr' => 'Yahoo',
            'ys' => 'Yahoo',
            'yt' => 'Yahoo',
            'yu' => 'Yahoo',
            'yv' => 'Yahoo',
            'yw' => 'Yahoo',
            'yx' => 'Yahoo',
            'yy' => 'Yahoo',
            'yz' => 'Yahoo',
            'za' => 'Zalo',
            'zb' => 'Zalo',
            'zc' => 'Zalo',
            'zd' => 'Zalo',
            'ze' => 'Zalo',
            'zf' => 'Zalo',
            'zg' => 'Zalo',
            'zh' => 'Zalo',
            'zi' => 'Zalo',
            'zj' => 'Zalo',
            'zk' => 'Zalo',
            'zl' => 'Zalo',
            'zm' => 'Zalo',
            'zn' => 'Zalo',
            'zo' => 'Zalo',
            'zp' => 'Zalo',
            'zq' => 'Zalo',
            'zr' => 'Zalo',
            'zs' => 'Zalo',
            'zt' => 'Zalo',
            'zu' => 'Zalo',
            'zv' => 'Zalo',
            'zw' => 'Zalo',
            'zx' => 'Zalo',
            'zy' => 'Zalo',
            'zz' => 'Zalo',
        ];

        return $services[$code] ?? ucfirst($code);
    }
}
