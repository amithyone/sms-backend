<?php

namespace App\Services;

use App\Models\SmsService;
use App\Models\SmsOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsProviderService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = Http::timeout(30);
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

    // 5Sim API Methods
    private function get5SimCountries(array $config): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/guest/countries');

        if ($response->successful()) {
            $data = $response->json();
            return collect($data)->map(function ($country) {
                return [
                    'code' => $country['country'],
                    'name' => $country['title'],
                    'flag' => $country['flag'] ?? null
                ];
            })->toArray();
        }

        return [];
    }

    private function get5SimServices(array $config, string $country): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . "/v1/guest/products/{$country}/any");

        if ($response->successful()) {
            $data = $response->json();
            return collect($data)->map(function ($service) {
                return [
                    'name' => $service['name'],
                    'service' => $service['service'],
                    'cost' => $service['price'],
                    'count' => $service['count'] ?? 0
                ];
            })->toArray();
        }

        return [];
    }

    private function create5SimOrder(array $config, string $country, string $service): array
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->post($config['api_url'] . '/v1/user/buy/activation/' . $country . '/' . $service);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['id'],
                'phone_number' => $data['phone'],
                'cost' => $data['cost'],
                'status' => 'active',
                'expires_at' => now()->addMinutes(20)
            ];
        }

        throw new Exception('Failed to create 5Sim order: ' . $response->body());
    }

    private function get5SimSmsCode(array $config, string $orderId): ?string
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/user/check/' . $orderId);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['sms']) && !empty($data['sms'])) {
                return $data['sms'][0]['code'] ?? null;
            }
        }

        return null;
    }

    private function cancel5SimOrder(array $config, string $orderId): bool
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->get($config['api_url'] . '/v1/user/cancel/' . $orderId);

        return $response->successful();
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

    private function getTigerSmsServices(array $config, string $country): array
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

    private function createTigerSmsOrder(array $config, string $country, string $service): array
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

        throw new Exception('Failed to create Tiger SMS order: ' . $response->body());
    }

    private function getTigerSmsCode(array $config, string $orderId): ?string
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

    private function cancelTigerSmsOrder(array $config, string $orderId): bool
    {
        $response = $this->httpClient
            ->withHeaders(['Authorization' => 'Bearer ' . $config['api_key']])
            ->post($config['api_url'] . "/order/{$orderId}/cancel");

        return $response->successful();
    }

    private function getTigerSmsBalance(array $config): float
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
}
