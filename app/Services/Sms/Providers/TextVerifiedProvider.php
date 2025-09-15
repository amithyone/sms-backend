<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsService;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class TextVerifiedProvider implements ProviderInterface
{
    private SimpleHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    public function getCountries(SmsService $smsService): array
    {
        // TextVerified effectively supports US only via public API
        return [ [ 'code' => 'US', 'name' => 'United States' ] ];
    }

    public function getServices(SmsService $smsService, string $country): array
    {
        $config = $smsService->getApiConfig();
        $query = 'numberType=mobile&reservationType=verification';
        $url = 'https://www.textverified.com/api/pub/v2/services?' . $query;

        // Attempt with Bearer first
        try {
            $bearer = $this->getBearer($config);
            $resp = $this->httpClient->get($url, [ 'headers' => [ 'Authorization' => 'Bearer ' . $bearer, 'Accept' => 'application/json' ] ]);
            Log::info('TextVerified getServices (bearer) HTTP', [ 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 300) ]);
            if ($resp->successful()) {
                $parsed = json_decode($resp->body(), true);
                $services = $this->parseServicesPayload($parsed);
                if (!empty($services)) return $services;
            }
        } catch (\Throwable $e) {
            Log::warning('TextVerified bearer fetch failed, will fallback to API headers', [ 'error' => $e->getMessage() ]);
        }

        // Fallback: API key headers without bearer
        $resp2 = $this->httpClient->get($url, [ 'headers' => [
            'X-API-KEY' => $config['api_key'] ?? '',
            'X-API-USERNAME' => $config['settings']['username'] ?? '',
            'Accept' => 'application/json',
        ]]);
        Log::info('TextVerified getServices (api headers) HTTP', [ 'status' => $resp2->status(), 'body_sample' => substr($resp2->body(), 0, 300) ]);
        if (!$resp2->successful()) {
            Log::error('TextVerified getServices HTTP (fallback)', [ 'url' => $url, 'status' => $resp2->status(), 'body_sample' => substr($resp2->body(), 0, 300) ]);
            return [];
        }
        $parsed2 = json_decode($resp2->body(), true);
        return $this->parseServicesPayload($parsed2);
    }

    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        $config = $smsService->getApiConfig();
        $bearer = $this->getBearer($config);
        $url = 'https://www.textverified.com/api/pub/v2/verifications';
        $headers = [ 'Authorization' => 'Bearer ' . $bearer, 'Content-Type' => 'application/json' ];
        $payload = [ 'serviceName' => $service, 'capability' => 'sms' ];
        Log::info('TextVerified createOrder request', [ 'url' => $url, 'payload' => $payload ]);
        $resp = $this->httpClient->post($url, [ 'headers' => $headers, 'json' => $payload ]);
        if (!$resp->successful()) {
            Log::error('TextVerified createOrder HTTP', [ 'url' => $url, 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 300) ]);
            throw new Exception('Failed to create TextVerified order: HTTP ' . $resp->status());
        }
        $data = json_decode($resp->body(), true);
        if (!is_array($data) || !isset($data['href'])) {
            Log::error('TextVerified createOrder invalid response', [ 'response' => $data ]);
            throw new Exception('Invalid response from TextVerified API');
        }
        $details = $this->getVerificationDetails($bearer, $data['href']);
        return [
            'order_id' => $data['href'],
            'phone_number' => $details['phoneNumber'] ?? '',
            'cost' => (float)($details['cost'] ?? 0),
            'status' => $details['state'] ?? 'pending',
            'expires_at' => $details['expiresAt'] ?? null,
        ];
    }

    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        $config = $smsService->getApiConfig();
        $bearer = $this->getBearer($config);
        $details = $this->getVerificationDetails($bearer, $orderId);
        if (($details['state'] ?? '') === 'verificationCompleted') {
            return $details['code'] ?? null;
        }
        return null;
    }

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $config = $smsService->getApiConfig();
        $bearer = $this->getBearer($config);
        $resp = $this->httpClient->delete($orderId, [ 'headers' => [ 'Authorization' => 'Bearer ' . $bearer, 'Content-Type' => 'application/json' ] ]);
        return $resp->successful();
    }

    public function getBalance(SmsService $smsService): float
    {
        // Not available via public API
        return 0.0;
    }

    private function getBearer(array $config): string
    {
        $apiKey = trim((string)($config['api_key'] ?? ''));
        $username = trim((string)($config['settings']['username'] ?? ''));
        $headers = [
            'X-API-KEY' => $apiKey,
            'X-API-USERNAME' => $username,
            'Accept' => 'application/json',
        ];
        $urls = [
            'https://www.textverified.com/api/pub/v2/auth',
            'https://textverified.com/api/pub/v2/auth',
        ];
        Log::info('TextVerified getBearerToken request', [ 'has_api_key' => !empty($apiKey), 'username' => $username ]);
        $lastError = null;
        foreach ($urls as $url) {
            try {
                $resp = $this->httpClient->post($url, [ 'headers' => $headers ]);
                Log::info('TextVerified auth attempt', [ 'url' => $url, 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 200) ]);
                if ($resp->successful()) {
                    $data = json_decode($resp->body(), true);
                    if (is_array($data) && isset($data['token'])) {
                        return (string)$data['token'];
                    }
                    $lastError = 'Invalid auth response';
                } else {
                    $lastError = 'HTTP ' . $resp->status();
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }
        throw new Exception('Failed to get TextVerified bearer token: ' . ($lastError ?? 'unknown error'));
    }

    private function getVerificationDetails(string $bearer, string $href): array
    {
        $resp = $this->httpClient->get($href, [ 'headers' => [ 'Authorization' => 'Bearer ' . $bearer, 'Content-Type' => 'application/json' ] ]);
        if (!$resp->successful()) {
            Log::error('TextVerified getVerificationDetails HTTP', [ 'url' => $href, 'status' => $resp->status(), 'body_sample' => substr($resp->body(), 0, 300) ]);
            throw new Exception('Failed to get TextVerified verification details: HTTP ' . $resp->status());
        }
        $data = json_decode($resp->body(), true);
        if (!is_array($data) || !isset($data['data'])) {
            throw new Exception('Invalid response from TextVerified verification details API');
        }
        return $data['data'];
    }

    /**
     * Normalize TextVerified services payload into [{ service, name, cost, count }]
     */
    private function parseServicesPayload($payload): array
    {
        $list = [];
        if (is_array($payload)) {
            $rows = $payload;
            if (isset($payload['data']) && is_array($payload['data'])) {
                $rows = $payload['data'];
            }
            foreach ($rows as $service) {
                if (is_array($service) && isset($service['serviceName'])) {
                    $list[] = [
                        'service' => $service['serviceName'],
                        'name' => $service['serviceName'],
                        'cost' => isset($service['minPrice']) ? (float)$service['minPrice'] : 0,
                        'count' => isset($service['available']) ? (int)$service['available'] : 0,
                    ];
                }
            }
        }
        return $list;
    }
}
