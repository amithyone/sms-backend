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

    public function initiateVirtualAccount(int $userId, float $amount): array
    {
        if (empty($this->baseUrl) || empty($this->secretKey)) {
            return [
                'success' => false,
                'message' => 'PayVibe is not configured. Set PAYVIBE_* env vars.',
            ];
        }

        $payload = [
            'amount' => $amount,
            'customer_reference' => 'USER_' . $userId,
            'product_identifier' => $this->productIdentifier,
            // Optional: metadata to help reconcile
            'metadata' => [ 'user_id' => $userId ],
        ];

        $url = $this->baseUrl . '/' . $this->virtualAccountEndpoint;

        try {
            $resp = $this->makeClient()->post($url, $payload);
            $json = $resp->json();

            if ($resp->successful()) {
                // Try to normalize common response fields
                $data = $json['data'] ?? $json;
                return [
                    'success' => true,
                    'data' => [
                        'reference' => (string)($data['reference'] ?? $data['ref'] ?? $data['transaction_reference'] ?? ''),
                        'account_number' => (string)($data['account_number'] ?? $data['accountNumber'] ?? ''),
                        'bank_name' => (string)($data['bank_name'] ?? $data['bankName'] ?? 'Wema Bank'),
                        'account_name' => (string)($data['account_name'] ?? $data['accountName'] ?? 'PAYVIBE'),
                        'amount' => (float)($data['amount'] ?? $amount),
                        'charge' => (float)($data['charge'] ?? 0),
                        'final_amount' => (float)($data['final_amount'] ?? ($data['amount'] ?? $amount)),
                        'expiry' => (int)($data['expiry'] ?? $data['expires_in'] ?? 0),
                        'transaction_id' => (string)($data['transaction_id'] ?? $data['id'] ?? ''),
                    ],
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


