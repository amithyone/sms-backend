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
        
        $baseUrl = 'https://www.textverified.com/api/pub/v2/services';
        // Request larger page size to minimize pagination hops
        $query = 'numberType=mobile&reservationType=verification&page[size]=100';
        $firstUrl = $baseUrl . '?' . $query;

        // Try bearer-auth paginated fetch first; fall back to API-key headers
        try {
            $bearer = $this->getBearer($config);
            $headers = [ 'Authorization' => 'Bearer ' . $bearer, 'Accept' => 'application/json' ];
            $all = $this->fetchAllTextVerifiedPages($firstUrl, $headers);
            if (!empty($all)) return $all;
        } catch (\Throwable $e) {
            Log::warning('TextVerified bearer pagination failed, trying API key headers', [ 'error' => $e->getMessage() ]);
        }

        $headers2 = [
            'X-API-KEY' => $config['api_key'] ?? '',
            'X-API-USERNAME' => $config['settings']['username'] ?? '',
            'Accept' => 'application/json',
        ];
        try {
            return $this->fetchAllTextVerifiedPages($firstUrl, $headers2);
        } catch (\Throwable $e) {
            Log::error('TextVerified getServices failed (api headers)', [ 'error' => $e->getMessage() ]);
            return [];
        }
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
     * Fetch all pages from TextVerified services endpoint following JSON:API links.next
     */
    private function fetchAllTextVerifiedPages(string $firstUrl, array $headers): array
    {
        $collected = [];
        $visited = 0;
        $maxPages = 15;
        $nextUrl = $firstUrl;

        while ($nextUrl && $visited < $maxPages) {
            try {
                $resp = $this->httpClient->get($nextUrl, [ 'headers' => $headers ]);
            } catch (\Throwable $e) {
                Log::warning('TextVerified page request threw', [ 'url' => $nextUrl, 'error' => $e->getMessage() ]);
                break;
            }
            Log::info('TextVerified getServices page', [ 'url' => $nextUrl, 'status' => $resp->status() ]);
            if (!$resp->successful()) {
                break;
            }
            $payload = json_decode($resp->body(), true);
            $rows = $this->parseServicesPayload($payload);
            if (!empty($rows)) {
                $collected = array_merge($collected, $rows);
            }
            $visited++;

            $next = null;
            if (is_array($payload)) {
                if (isset($payload['links']) && is_array($payload['links']) && !empty($payload['links']['next'])) {
                    $next = $payload['links']['next'];
                } elseif (isset($payload['meta']) && isset($payload['meta']['page']) && isset($payload['meta']['pages'])) {
                    $page = (int)$payload['meta']['page'];
                    $pages = (int)$payload['meta']['pages'];
                    if ($pages > $page) {
                        // Preserve existing query and just update page[number]
                        $parsed = parse_url($firstUrl);
                        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'www.textverified.com') . ($parsed['path'] ?? '/api/pub/v2/services');
                        $q = [];
                        if (!empty($parsed['query'])) { parse_str($parsed['query'], $q); }
                        $q['page[number]'] = $page + 1;
                        $next = $base . '?' . http_build_query($q);
                    }
                }
            }
            $nextUrl = $next;
        }

        // De-duplicate by service name
        $out = [];
        $seen = [];
        foreach ($collected as $row) {
            $key = is_array($row) ? ($row['service'] ?? $row['name'] ?? null) : null;
            if ($key && !isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * Get services via Python client bridge
     */
    private function getServicesViaPythonClient(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        $username = $config['settings']['username'] ?? '';
        
        // Create temporary Python script
        $scriptPath = sys_get_temp_dir() . '/textverified_services_' . uniqid() . '.py';
        $scriptContent = "#!/usr/bin/env python3
from textverified import TextVerified
from textverified import NumberType, ReservationType
import json

client = TextVerified(
    api_key=\"{$apiKey}\",
    api_username=\"{$username}\",
)

try:
    services = client.services.list(
        number_type=NumberType.MOBILE,
        reservation_type=ReservationType.VERIFICATION
    )
    
    # Return ALL services as JSON (no artificial limit)
    result = []
    for service in services:
        result.append({
            'service': service.service_name,
            'name': service.service_name,
            'cost': 0.0,  # Cost not available in this endpoint
            'count': 1,
            'provider': 'textverified',
            'provider_name': 'TextVerified'
        })
    
    print(json.dumps(result))
except Exception as e:
    print(json.dumps([]))
";
        
        file_put_contents($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        try {
            $output = shell_exec("python3 $scriptPath 2>&1");
            $services = json_decode($output, true);
            
            // Clean up
            unlink($scriptPath);
            
            return is_array($services) ? $services : [];
        } catch (\Throwable $e) {
            // Clean up on error
            if (file_exists($scriptPath)) {
                unlink($scriptPath);
            }
            throw $e;
        }
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
