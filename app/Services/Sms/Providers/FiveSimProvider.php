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
                ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
                ->get($config['api_url'] . '/v1/guest/countries');
            if ($countriesResp->successful()) {
                $list = $countriesResp->json();
                $slugs = [];
                if (is_array($list)) {
                    foreach ($list as $row) {
                        if (is_array($row) && !empty($row['country'])) { $slugs[strtolower($row['country'])] = true; }
                    }
                }
                if (empty($slugs[$countrySlug])) {
                    $countrySlug = null; // slug not supported by 5SIM
                }
            }
        }

        $response = null;
        if ($countrySlug) {
            $response = $this->httpClient
                ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
                ->get($config['api_url'] . "/v1/guest/products/{$countrySlug}/any");
        } else {
            // No valid slug for 5SIM: return empty to avoid failed number requests
            return [];
        }

        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data) && !empty($data)) {
                // Handle both list and associative map shapes
                $out = [];
                $isAssoc = array_keys($data) !== range(0, count($data) - 1);
                if ($isAssoc) {
                    foreach ($data as $product => $info) {
                        if (!is_array($info)) continue;
                        $usdPerRub = (float) config('services.sms_fx.usd_per_rub', 0.011);
                        $usdCost = (isset($info['Price']) ? (float)$info['Price'] : (isset($info['price']) ? (float)$info['price'] : 0)) * max($usdPerRub, 0.00001);
                        $out[] = [
                            'name' => $info['name'] ?? ucfirst((string)$product),
                            'service' => $info['service'] ?? (string)$product,
                            'cost' => $usdCost,
                            'currency' => 'USD',
                            'count' => isset($info['Qty']) ? (int)$info['Qty'] : (isset($info['count']) ? (int)$info['count'] : 0),
                        ];
                    }
                } else {
                    foreach ($data as $info) {
                        if (!is_array($info)) continue;
                        $usdPerRub = (float) config('services.sms_fx.usd_per_rub', 0.011);
                        $usdCost = (isset($info['Price']) ? (float)$info['Price'] : (isset($info['price']) ? (float)$info['price'] : 0)) * max($usdPerRub, 0.00001);
                        $out[] = [
                            'name' => $info['name'] ?? ($info['service'] ?? 'Service'),
                            'service' => $info['service'] ?? ($info['code'] ?? 'unknown'),
                            'cost' => $usdCost,
                            'currency' => 'USD',
                            'count' => isset($info['Qty']) ? (int)$info['Qty'] : (isset($info['count']) ? (int)$info['count'] : 0),
                        ];
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
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($apiBase . '/v1/guest/countries');
        if ($countriesResp->successful()) {
            $valid = [];
            $list = $countriesResp->json();
            if (is_array($list)) {
                foreach ($list as $row) {
                    if (is_array($row) && !empty($row['country'])) { $valid[strtolower($row['country'])] = true; }
                }
            }
            if (!$countrySlug || empty($valid[$countrySlug])) {
                // Reject if unsupported to avoid guaranteed failures
                throw new Exception('5SIM does not support country: ' . (string)$country);
            }
        }

        $product = $this->mapServiceCodeToProduct($service);
        $buyUrl = $apiBase . '/v1/user/buy/activation?country=' . urlencode($countrySlug) . '&operator=any&product=' . urlencode($product);
        $resp = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key'], 'Accept' => 'application/json'])
            ->post($buyUrl);
        Log::info('5SIM buy activation HTTP', [ 'url' => preg_replace('/(api_key|Authorization)=[^&]*/i', '$1=***', $buyUrl), 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 500) ]);

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

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $config = $smsService->getApiConfig();
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

    public function getBalance(SmsService $smsService): float
    {
        $config = $smsService->getApiConfig();
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/user/profile');
        if ($response->successful()) {
            $data = $response->json();
            return $data['balance'] ?? 0.0;
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
