<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\PayvibeService;

class WalletController extends Controller
{
    public function initiateTopUpPublic(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = (int) $request->input('user_id');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $amount = (float) $request->input('amount');
        $result = $payvibe->initiateVirtualAccount($userId, $amount);

        if (!empty($result['success'])) {
            // Record a pending deposit for this user to aid later verification
            try {
                $data = $result['data'] ?? [];
                if (!empty($data['reference'])) {
                    DB::table('deposits')->updateOrInsert(
                        ['reference' => (string) $data['reference']],
                        [
                            'user_id' => $userId,
                            'amount' => $data['final_amount'] ?? $amount,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Non-fatal for testing
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function initiateTopUpLegacy(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $amount = (float) $request->input('amount');

        $result = $payvibe->initiateVirtualAccount($user->id, $amount);

        if (!empty($result['success'])) {
            // Optionally record a pending deposit for reconciliation
            try {
                $data = $result['data'] ?? [];
                if (!empty($data['reference'])) {
                    DB::table('deposits')->updateOrInsert(
                        ['reference' => (string) $data['reference']],
                        [
                            'user_id' => $user->id,
                            'amount' => $data['final_amount'] ?? $amount,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Swallow DB errors to not block the flow
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function verifyTopUpLegacy(Request $request, PayvibeService $payvibe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = (string) $request->input('reference');
        $result = $payvibe->verifyPayment($reference);

        if (!empty($result['success'])) {
            $status = $result['data']['status'] ?? 'pending';
            $amount = $result['data']['amount'] ?? null;
            $user = Auth::user();

            try {
                if ($status === 'completed') {
                    // Mark deposit as completed and credit user balance if applicable
                    DB::transaction(function () use ($user, $reference, $amount) {
                        DB::table('deposits')
                            ->where('reference', $reference)
                            ->update(['status' => 'completed', 'updated_at' => now()]);

                        if (!is_null($amount)) {
                            DB::table('users')->where('id', $user->id)->increment('balance', (float)$amount);
                        }
                    });
                } elseif ($status === 'failed') {
                    DB::table('deposits')
                        ->where('reference', $reference)
                        ->update(['status' => 'failed', 'updated_at' => now()]);
                }
            } catch (\Throwable $e) {
                // Ignore accounting errors for this simple verify flow
            }

            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function getRecentDeposits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $deposits = DB::table('deposits')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $formattedDeposits = $deposits->map(function ($deposit) {
            return [
                'id' => $deposit->id,
                'amount' => (float) $deposit->amount,
                'reference' => (string) $deposit->reference,
                'status' => (string) $deposit->status,
                'created_at' => (string) $deposit->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedDeposits,
            'message' => 'Recent deposits retrieved successfully'
        ]);
    }

    public function initiateTopUp(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'amount' => 'required|integer|min:100|max:1000000'
        ]);

        $amount = (int) $validated['amount'];
        
        // Calculate charges based on tiered structure
        $charges = $this->calculateCharges($amount);
        $totalAmount = $amount + $charges;
        
        $reference = 'PAYVIBE_' . time() . '_' . rand(1000, 9999);

        try {
            // Call PayVibe API to create virtual account
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYVIBE_SECRET_KEY'),
                'Content-Type' => 'application/json',
                'X-Api-Key' => env('PAYVIBE_PUBLIC_KEY'),
                'Accept' => 'application/json'
            ])->post(env('PAYVIBE_BASE_URL') . '/api/v1/payments/virtual-accounts/initiate', [
                'amount' => $amount,
                'reference' => $reference,
                'customer_reference' => 'USER_' . $user->id,
                'product_identifier' => env('PAYVIBE_PRODUCT_IDENTIFIER', 'sms')
            ]);

            if (!$response->successful()) {
                Log::error('PayVibe API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create virtual account'
                ], 500);
            }

            $responseData = $response->json();
            
            // Store initial account information in metadata
            $initialMetadata = [
                'account_details' => $responseData['data'] ?? $responseData,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'created_at' => now()->toISOString()
            ];
            
            // Store deposit record
            $depositId = DB::table('deposits')->insertGetId([
                'user_id' => $user->id,
                'amount' => $amount,
                'charges' => $charges,
                'reference' => $reference,
                'status' => 'pending',
                'metadata' => json_encode($initialMetadata),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'account_number' => $responseData['data']['virtual_account_number'] ?? 'N/A',
                    'bank_name' => $responseData['data']['bank_name'] ?? 'N/A',
                    'account_name' => $responseData['data']['account_name'] ?? 'N/A',
                    'amount' => $amount,
                    'charges' => $charges,
                    'total_amount' => $totalAmount,
                    'status' => $responseData['data']['status'] ?? 'pending',
                    'expiry' => 1800,
                    'transaction_id' => $depositId
                ],
                'message' => 'Virtual account created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('PayVibe Initiate Error', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create virtual account'
            ], 500);
        }
    }

    public function verifyTopUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string'
        ]);

        $reference = $validated['reference'];
        $deposit = DB::table('deposits')->where('reference', $reference)->first();

        if (!$deposit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found'
            ], 404);
        }

        if ($deposit->status === 'completed') {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'completed',
                    'amount' => (float) $deposit->amount
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'status' => 'pending',
                'amount' => (float) $deposit->amount
            ]
        ]);
    }

    public function handlePayVibeWebhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('PayVibe Webhook Received', $payload);

            $reference = $payload['reference'] ?? null;
            $status = $payload['status'] ?? null;
            $amount = $payload['transaction_amount'] ?? null;

            if (!$reference) {
                return response()->json(['status' => 'error', 'message' => 'Missing reference'], 400);
            }

            $deposit = DB::table('deposits')->where('reference', $reference)->first();

            if (!$deposit) {
                Log::warning('PayVibe Webhook: Deposit not found', ['reference' => $reference]);
                return response()->json(['status' => 'error', 'message' => 'Deposit not found'], 404);
            }

            if ($deposit->status === 'completed') {
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // Calculate credit amount based on actual payment
            $actualPayment = $payload['transaction_amount'] ?? $deposit->amount;
            $creditAmount = $this->calculateCreditAmount($actualPayment);
            
            // Merge webhook data with existing metadata
            $existingMetadata = json_decode($deposit->metadata ?? '{}', true);
            $webhookMetadata = [
                'transaction_details' => $payload,
                'actual_payment' => $actualPayment,
                'credit_amount' => $creditAmount,
                'completed_at' => now()->toISOString()
            ];
            $updatedMetadata = array_merge($existingMetadata, $webhookMetadata);

            // Update deposit status with merged metadata
            DB::table('deposits')->where('id', $deposit->id)->update([
                'status' => 'completed',
                'actual_amount' => $actualPayment,
                'credit_amount' => $creditAmount,
                'metadata' => json_encode($updatedMetadata),
                'updated_at' => now()
            ]);

            // Credit user wallet
            DB::table('users')->where('id', $deposit->user_id)->increment('wallet_balance', $creditAmount);

            Log::info('PayVibe Webhook: Payment processed', [
                'reference' => $reference,
                'user_id' => $deposit->user_id,
                'amount' => $deposit->amount
            ]);

            return response()->json(['status' => 'success', 'message' => 'Payment processed']);

        } catch (\Exception $e) {
            Log::error('PayVibe Webhook Error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed'], 500);
        }
    }

    private function calculateCharges($amount)
    {
        if ($amount >= 1000 && $amount <= 10000) {
            return ($amount * 0.015) + 100;
        } elseif ($amount > 10000 && $amount <= 20000) {
            return ($amount * 0.015) + 200;
        } elseif ($amount > 20000 && $amount <= 40000) {
            return ($amount * 0.015) + 300;
        } else {
            return ($amount * 0.02) + 200;
        }
    }

    private function calculateCreditAmount($actualPayment)
    {
        // Calculate charges for the actual payment amount
        $charges = $this->calculateCharges($actualPayment);
        
        // Credit amount is actual payment minus charges
        $creditAmount = $actualPayment - $charges;
        
        // Ensure minimum credit of 0
        return max(0, $creditAmount);
    }
}
