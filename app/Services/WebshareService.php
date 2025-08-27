<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WebshareService
{
    private $baseUrl;
    private $apiKey;
    private $httpClient;

    public function __construct()
    {
        $this->baseUrl = 'https://proxy.webshare.io/api/v2';
        $this->apiKey = env('WEBSHARE_API_KEY');
        
        $this->httpClient = Http::withHeaders([
            'Authorization' => 'Token ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/profile/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Profile retrieved successfully'
                ];
            }

            Log::error('Webshare getUserProfile failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get user profile'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getUserProfile exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get user profile: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get proxy list
     */
    public function getProxyList(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/proxy/list/';
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = $this->httpClient->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy list retrieved successfully'
                ];
            }

            Log::error('Webshare getProxyList failed', [
                'params' => $params,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy list'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getProxyList exception', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy list: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download proxy list
     */
    public function downloadProxyList(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/proxy/list/download/';
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = $this->httpClient->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy list download link generated successfully'
                ];
            }

            Log::error('Webshare downloadProxyList failed', [
                'params' => $params,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to generate proxy list download link'
            ];
        } catch (Exception $e) {
            Log::error('Webshare downloadProxyList exception', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to generate proxy list download link: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Refresh proxy list
     */
    public function refreshProxyList(): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/proxy/list/refresh/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy list refreshed successfully'
                ];
            }

            Log::error('Webshare refreshProxyList failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to refresh proxy list'
            ];
        } catch (Exception $e) {
            Log::error('Webshare refreshProxyList exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to refresh proxy list: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get proxy statistics
     */
    public function getProxyStats(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/proxy/stats/';
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = $this->httpClient->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy statistics retrieved successfully'
                ];
            }

            Log::error('Webshare getProxyStats failed', [
                'params' => $params,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy statistics'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getProxyStats exception', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription plans
     */
    public function getSubscriptionPlans(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/subscription/plan/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Subscription plans retrieved successfully'
                ];
            }

            Log::error('Webshare getSubscriptionPlans failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get subscription plans'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getSubscriptionPlans exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get subscription plans: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available assets
     */
    public function getAvailableAssets(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/subscription/available_assets/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Available assets retrieved successfully'
                ];
            }

            Log::error('Webshare getAvailableAssets failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get available assets'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getAvailableAssets exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get available assets: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get proxy configuration
     */
    public function getProxyConfig(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/proxy/config/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy configuration retrieved successfully'
                ];
            }

            Log::error('Webshare getProxyConfig failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy configuration'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getProxyConfig exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy configuration: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create IP authorization
     */
    public function createIpAuthorization(string $ip): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/proxy/ip_authorization/', [
                'ip' => $ip
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'IP authorization created successfully'
                ];
            }

            Log::error('Webshare createIpAuthorization failed', [
                'ip' => $ip,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to create IP authorization'
            ];
        } catch (Exception $e) {
            Log::error('Webshare createIpAuthorization exception', [
                'ip' => $ip,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to create IP authorization: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get IP authorizations
     */
    public function getIpAuthorizations(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/proxy/ip_authorization/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'IP authorizations retrieved successfully'
                ];
            }

            Log::error('Webshare getIpAuthorizations failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get IP authorizations'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getIpAuthorizations exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get IP authorizations: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete IP authorization
     */
    public function deleteIpAuthorization(int $authorizationId): array
    {
        try {
            $response = $this->httpClient->delete($this->baseUrl . '/proxy/ip_authorization/' . $authorizationId . '/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => null,
                    'message' => 'IP authorization deleted successfully'
                ];
            }

            Log::error('Webshare deleteIpAuthorization failed', [
                'authorization_id' => $authorizationId,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to delete IP authorization'
            ];
        } catch (Exception $e) {
            Log::error('Webshare deleteIpAuthorization exception', [
                'authorization_id' => $authorizationId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to delete IP authorization: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get "What's my IP" information
     */
    public function getMyIp(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/proxy/ip_authorization/whats_my_ip/');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'IP information retrieved successfully'
                ];
            }

            Log::error('Webshare getMyIp failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get IP information'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getMyIp exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get IP information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get proxy activities
     */
    public function getProxyActivities(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/proxy/activity/';
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = $this->httpClient->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy activities retrieved successfully'
                ];
            }

            Log::error('Webshare getProxyActivities failed', [
                'params' => $params,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy activities'
            ];
        } catch (Exception $e) {
            Log::error('Webshare getProxyActivities exception', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get proxy activities: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download proxy activities
     */
    public function downloadProxyActivities(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/proxy/activity/download/';
            if ($queryParams) {
                $url .= '?' . $queryParams;
            }

            $response = $this->httpClient->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Proxy activities download link generated successfully'
                ];
            }

            Log::error('Webshare downloadProxyActivities failed', [
                'params' => $params,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to generate proxy activities download link'
            ];
        } catch (Exception $e) {
            Log::error('Webshare downloadProxyActivities exception', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to generate proxy activities download link: ' . $e->getMessage()
            ];
        }
    }
}
