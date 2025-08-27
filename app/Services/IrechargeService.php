<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class IrechargeService
{
    private $baseUrl;
    private $username;
    private $password;
    private $httpClient;

    public function __construct()
    {
        $this->baseUrl = env('IRECHARGE_BASE_URL', 'https://irecharge.com.ng/pwr_api_sandbox/');
        $this->username = env('IRECHARGE_USERNAME');
        $this->password = env('IRECHARGE_PASSWORD');
        
        $this->httpClient = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Get available networks for airtime
     */
    public function getAirtimeNetworks(): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'get_airtime_networks.php', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            }

            Log::error('iRecharge getAirtimeNetworks failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('iRecharge getAirtimeNetworks exception', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get available networks for data bundles
     */
    public function getDataNetworks(): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'get_data_networks.php', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            }

            Log::error('iRecharge getDataNetworks failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('iRecharge getDataNetworks exception', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get data bundles for a specific network
     */
    public function getDataBundles(string $network): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'get_data_bundles.php', [
                'username' => $this->username,
                'password' => $this->password,
                'network' => $network,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            }

            Log::error('iRecharge getDataBundles failed', [
                'network' => $network,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('iRecharge getDataBundles exception', [
                'network' => $network,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Purchase airtime
     */
    public function purchaseAirtime(string $network, string $phone, float $amount, string $reference): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'purchase_airtime.php', [
                'username' => $this->username,
                'password' => $this->password,
                'network' => $network,
                'phone' => $phone,
                'amount' => $amount,
                'reference' => $reference,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => isset($data['status']) && $data['status'] === 'success',
                    'data' => $data,
                    'message' => $data['message'] ?? 'Airtime purchase completed'
                ];
            }

            Log::error('iRecharge purchaseAirtime failed', [
                'network' => $network,
                'phone' => $phone,
                'amount' => $amount,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => $response->json(),
                'message' => 'Airtime purchase failed'
            ];
        } catch (Exception $e) {
            Log::error('iRecharge purchaseAirtime exception', [
                'network' => $network,
                'phone' => $phone,
                'amount' => $amount,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Airtime purchase failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Purchase data bundle
     */
    public function purchaseDataBundle(string $network, string $phone, string $plan, string $reference): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'purchase_data_bundle.php', [
                'username' => $this->username,
                'password' => $this->password,
                'network' => $network,
                'phone' => $phone,
                'plan' => $plan,
                'reference' => $reference,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => isset($data['status']) && $data['status'] === 'success',
                    'data' => $data,
                    'message' => $data['message'] ?? 'Data bundle purchase completed'
                ];
            }

            Log::error('iRecharge purchaseDataBundle failed', [
                'network' => $network,
                'phone' => $phone,
                'plan' => $plan,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => $response->json(),
                'message' => 'Data bundle purchase failed'
            ];
        } catch (Exception $e) {
            Log::error('iRecharge purchaseDataBundle exception', [
                'network' => $network,
                'phone' => $phone,
                'plan' => $plan,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Data bundle purchase failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $reference): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'get_transaction_status.php', [
                'username' => $this->username,
                'password' => $this->password,
                'reference' => $reference,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => isset($data['status']) && $data['status'] === 'success',
                    'data' => $data,
                    'message' => $data['message'] ?? 'Transaction status retrieved'
                ];
            }

            Log::error('iRecharge getTransactionStatus failed', [
                'reference' => $reference,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'data' => $response->json(),
                'message' => 'Failed to get transaction status'
            ];
        } catch (Exception $e) {
            Log::error('iRecharge getTransactionStatus exception', [
                'reference' => $reference,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to get transaction status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . 'get_balance.php', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'balance' => $data['balance'] ?? 0,
                        'message' => 'Balance retrieved successfully'
                    ];
                }
            }

            Log::error('iRecharge getBalance failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'balance' => 0,
                'message' => 'Failed to get balance'
            ];
        } catch (Exception $e) {
            Log::error('iRecharge getBalance exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'balance' => 0,
                'message' => 'Failed to get balance: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate phone number
     */
    public function validatePhoneNumber(string $phone, string $network): bool
    {
        // Basic validation - you can enhance this based on iRecharge requirements
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) !== 11) {
            return false;
        }

        // Network-specific validation
        $networkPrefixes = [
            'mtn' => ['0803', '0806', '0813', '0816', '0814', '0810', '0903', '0906', '0703', '0706'],
            'airtel' => ['0802', '0808', '0812', '0701', '0708', '0902', '0907', '0901'],
            'glo' => ['0805', '0807', '0811', '0815', '0705', '0905'],
            '9mobile' => ['0809', '0817', '0818', '0908', '0909'],
        ];

        if (isset($networkPrefixes[$network])) {
            $prefix = substr($phone, 0, 4);
            return in_array($prefix, $networkPrefixes[$network]);
        }

        return true; // If network not found, assume valid
    }
}
