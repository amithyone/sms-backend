<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsService;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TigerSmsProvider implements ProviderInterface
{
    private SimpleHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    public function getCountries(SmsService $smsService): array
    {
        $config = $smsService->getApiConfig();
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
                foreach ($data as $key => $entry) {
                    if (is_numeric($key) && is_array($entry)) {
                        $code = (string)$key;
                        $name = $entry['eng'] ?? ($entry['english'] ?? ($entry['name'] ?? 'Country ' . $code));
                        $countries[] = ['code' => $code, 'name' => $name];
                    }
                }
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
                    usort($countries, fn($a,$b) => strcmp($a['name'], $b['name']));
                    return $countries;
                }
            }
        }

        // Fallback: derive countries from getPrices
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
            foreach ($data as $maybeCountryId => $servicesMap) {
                if (is_numeric($maybeCountryId) && is_array($servicesMap)) {
                    $countryIds[(string)$maybeCountryId] = true;
                }
            }
            $countries = [];
            foreach (array_keys($countryIds) as $id) {
                $countries[] = [ 'code' => (string)$id, 'name' => 'Country ' . (string)$id ];
            }
            usort($countries, fn($a,$b) => strcmp($a['name'], $b['name']));
            return $countries;
        }

        return [];
    }

    public function getServices(SmsService $smsService, string $country): array
    {
        $config = $smsService->getApiConfig();
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

            if (!is_string($serviceCode)) continue;
            $services[] = [
                'name' => strtoupper($serviceCode),
                'service' => $serviceCode,
                'cost' => $cost !== null ? $cost : 0,
                'count' => $count,
            ];
        }

        usort($services, fn($a,$b) => ($a['cost'] <=> $b['cost']));
        return $services;
    }

    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        $config = $smsService->getApiConfig();
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
            if (stripos($body, 'ACCESS_NUMBER') === 0) {
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

    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        $config = $smsService->getApiConfig();
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

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $config = $smsService->getApiConfig();
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

    public function getBalance(SmsService $smsService): float
    {
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode((string)($config['api_key'] ?? '')) . '&action=getBalance';
        $response = $this->httpClient->get($url);
        $body = trim($response->body());
        if ($response->successful() && stripos($body, 'ACCESS_BALANCE') === 0) {
            $parts = explode(':', $body, 2);
            return isset($parts[1]) ? (float)$parts[1] : 0.0;
        }
        return 0.0;
    }

    private function sanitizeUrl(string $url): string
    {
        return preg_replace('/(api_key=)[^&]*/i', '$1***', $url) ?? $url;
    }
}
