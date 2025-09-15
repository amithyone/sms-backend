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
        // JSON products endpoint first
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
                    $countryName = is_numeric($country) ? null : strtolower((string)$country);
                }
                if ($countryName) {
                    foreach ($services as &$svc) {
                        $prices = $this->getPricesByProduct($config, $svc['service'], $countryName);
                        if (!empty($prices) && isset($prices[0]['cost'])) {
                            $svc['cost'] = (float)$prices[0]['cost'];
                        }
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
        Log::info('5SIM getNumber HTTP', [ 'url' => $url, 'status' => $resp->status(), 'body_sample' => substr($body, 0, 300) ]);

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
}
