<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PayvibeService
{
    private string $baseUrl;
    private ?string $publicKey;
    private ?string $secretKey;
    private string $productIdentifier;
    private string $virtualAccountEndpoint;
    private string $verifyEndpoint;

    public function __construct()
    {
        $cfg = config('services.payment.payvibe');
        $this->baseUrl = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $this->publicKey = $cfg['public_key'] ?? null;
        $this->secretKey = $cfg['secret_key'] ?? null;
        $this->productIdentifier = (string)($cfg['product_identifier'] ?? 'sms');
        // Allow overriding endpoints via env if PayVibe path differs
        $this->virtualAccountEndpoint = trim((string) env('PAYVIBE_VA_ENDPOINT', '/virtual-accounts/initiate'), '/');
        $this->verifyEndpoint = trim((string) env('PAYVIBE_VERIFY_ENDPOINT', '/payments/verify'), '/');
    }

    private function makeClient(): SimpleHttpClient
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->secretKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->secretKey;
        }
        if (!empty($this->publicKey)) {
            $headers['X-Api-Key'] = $this->publicKey;
        }

        return new SimpleHttpClient([
            'headers' => $headers,
            'timeout' => 15,
        ]);
    }

    public function initiateVirtualAccount(int $userId, ?float $amount = null): array
    {
        if (empty($this->baseUrl) || empty($this->secretKey)) {
            return [
                'success' => false,
                'message' => 'PayVibe is not configured. Set PAYVIBE_* env vars.',
            ];
        }

        // Generate unique reference
        $reference = 'PV_' . time() . '_' . $userId . '_' . rand(1000, 9999);

        // Do NOT send amount so PayVibe accepts any transfer value; use webhook actuals later
        $payload = [
            'reference' => $reference,
            'customer_reference' => 'USER_' . $userId,
            'product_identifier' => $this->productIdentifier,
            'metadata' => [ 'user_id' => $userId, 'intended_amount' => $amount ],
        ];

        $url = $this->baseUrl . '/' . $this->virtualAccountEndpoint;

        try {
            $resp = $this->makeClient()->post($url, $payload);
            $json = $resp->json();

            if ($resp->successful()) {
                // Log the raw response for debugging
                Log::info('PayVibe raw response', ['response' => $json]);
                
                // Try to normalize common response fields
                $data = $json['data'] ?? $json;
                return [
                    'success' => true,
                    'data' => [
                        'reference' => (string)($data['reference'] ?? $data['ref'] ?? $data['transaction_reference'] ?? $reference ?? ''),
                        'account_number' => (string)($data['virtual_account_number'] ?? $data['account_number'] ?? $data['accountNumber'] ?? $data['accountno'] ?? $data['account_no'] ?? ''),
                        'bank_name' => (string)($data['bank_name'] ?? $data['bankName'] ?? $data['bank'] ?? 'Wema Bank'),
                        'account_name' => (string)($data['account_name'] ?? $data['accountName'] ?? $data['name'] ?? 'Finspa/PAYVIBE'),
                        'amount' => isset($data['amount']) ? (float)$data['amount'] : null,
                        'charge' => isset($data['charge']) ? (float)$data['charge'] : null,
                        'final_amount' => isset($data['final_amount']) ? (float)$data['final_amount'] : null,
                        'expiry' => (int)($data['expiry'] ?? $data['expires_in'] ?? $data['duration'] ?? 0),
                        'transaction_id' => (string)($data['transaction_id'] ?? $data['id'] ?? $data['txn_id'] ?? ''),
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? 'PayVibe initiation failed',
                'data' => $json,
                'status' => $resp->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('PayVibe initiate error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to reach PayVibe',
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        if (empty($this->baseUrl) || empty($this->secretKey)) {
            return [
                'success' => false,
                'message' => 'PayVibe is not configured. Set PAYVIBE_* env vars.',
            ];
        }

        $payload = [ 'reference' => $reference ];
        $url = $this->baseUrl . '/' . $this->verifyEndpoint;

        try {
            $resp = $this->makeClient()->post($url, $payload);
            $json = $resp->json();

            if ($resp->successful()) {
                $data = $json['data'] ?? $json;
                $status = strtolower((string)($data['status'] ?? 'pending'));
                return [
                    'success' => true,
                    'data' => [
                        'status' => in_array($status, ['success','successful','completed']) ? 'completed' : ($status === 'failed' ? 'failed' : 'pending'),
                        'amount' => isset($data['amount']) ? (float)$data['amount'] : null,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? 'PayVibe verify failed',
                'data' => $json,
                'status' => $resp->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('PayVibe verify error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to reach PayVibe',
            ];
        }
    }
}


