<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsService;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * SMSPool provider integration
 * Docs: https://documenter.getpostman.com/view/30155063/2s9YXmZ1JY
 */
class SmsPoolProvider implements ProviderInterface
{
    private SimpleHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    private function getBaseUrl(SmsService $svc): string
    {
        $cfg = $svc->getApiConfig();
        return rtrim((string)($cfg['api_url'] ?? 'https://api.smspool.net'), '/');
    }

    private function apiKey(SmsService $svc): string
    {
        return (string)($svc->getApiConfig()['api_key'] ?? '');
    }

    public function getCountries(SmsService $smsService): array
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        $resp = $client->post($base . '/request/pricing', ['key' => $key]);
        if (!$resp->successful()) return [];
        $rows = $resp->json();
        if (!is_array($rows)) return [];
        $countries = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $iso = (string)($row['short_name'] ?? '');
            $name = (string)($row['country_name'] ?? '');
            if ($iso && $name) {
                $countries[$iso] = $name;
            }
        }
        $out = [];
        foreach ($countries as $iso => $name) {
            $out[] = ['code' => $iso, 'name' => $name];
        }
        return $out;
    }

    public function getServices(SmsService $smsService, string $country): array
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);

        $isNumericCountry = ctype_digit($country);
        $payload = ['key' => $key];
        if ($isNumericCountry) {
            $payload['country'] = (int) $country;
        }

        $resp = $client->post($base . '/request/pricing', $payload);
        Log::info('SMSPool getServices', ['endpoint' => '/request/pricing', 'status' => $resp->status(), 'numericCountry' => $isNumericCountry]);
        if (!$resp->successful()) return [];
        $rows = $resp->json();
        if (!is_array($rows)) $rows = [];

        if (!$isNumericCountry) {
            $iso = strtoupper($country);
            $rows = array_values(array_filter($rows, function ($row) use ($iso) {
                return is_array($row) && strtoupper((string)($row['short_name'] ?? '')) === $iso;
            }));
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $serviceId = (string)($row['service'] ?? '');
            $serviceName = (string)($row['service_name'] ?? '');
            $price = (float)($row['price'] ?? 0);
            if ($serviceId === '' && $serviceName === '') continue;
            $out[] = [
                'service' => $serviceId !== '' ? $serviceId : $serviceName,
                'name' => $serviceName !== '' ? $serviceName : $serviceId,
                'cost' => $price,
                'count' => 0,
            ];
        }
        return $out;
    }

    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        $payload = [
            'key' => $key,
            'country' => $country,
            'service' => $service,
        ];
        $resp = $client->post($base . '/purchase/sms', $payload);
        if (!$resp->successful()) {
            throw new Exception('SMSPool create order failed: HTTP ' . $resp->status());
        }
        $data = $resp->json();
        if (!is_array($data) || empty($data['success'])) {
            $msg = is_array($data) && isset($data['message']) ? (string)$data['message'] : 'unknown error';
            throw new Exception('SMSPool create order returned error: ' . $msg);
        }
        return [
            'order_id' => (string)($data['order_id'] ?? $data['orderid'] ?? ''),
            'phone_number' => (string)($data['number'] ?? ''),
            'cost' => (float)($data['cost'] ?? 0),
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
        ];
    }

    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        $resp = $client->post($base . '/sms/check', ['orderid' => $orderId, 'key' => $key]);
        if (!$resp->successful()) return null;
        $data = $resp->json();
        if (is_array($data) && isset($data['sms']) && $data['sms'] !== '') return (string)$data['sms'];
        if (is_array($data) && !empty($data['code'])) return (string)$data['code'];
        return null;
    }

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        $resp = $client->post($base . '/sms/cancel', ['orderid' => $orderId, 'key' => $key]);
        if (!$resp->successful()) return false;
        $data = $resp->json();
        return is_array($data) ? (bool)($data['success'] ?? false) : false;
    }

    public function getBalance(SmsService $smsService): float
    {
        $base = $this->getBaseUrl($smsService);
        $key = $this->apiKey($smsService);
        $client = $this->httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $key
        ]);
        $resp = $client->get($base . '/business/users');
        if (!$resp->successful()) return 0.0;
        $data = $resp->json();
        if (is_array($data) && isset($data[0]) && is_array($data[0]) && isset($data[0]['balance'])) {
            return (float)$data[0]['balance'];
        }
        return 0.0;
    }
}



