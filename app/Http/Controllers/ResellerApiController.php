<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SmsProviderService;
use App\Services\VtuNgService;
use App\Models\SmsOrder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResellerApiController extends Controller
{
    private $smsProviderService;
    private $vtuService;

    public function __construct(SmsProviderService $smsProviderService, VtuNgService $vtuService)
    {
        $this->smsProviderService = $smsProviderService;
        $this->vtuService = $vtuService;
    }

    /**
     * Get API user info and balance
     */
    public function getInfo(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');
        $apiKey = $request->attributes->get('api_key');

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => (float) $user->balance,
                'api_key_name' => $apiKey->name,
                'permissions' => $apiKey->permissions ?? ['all'],
                'rate_limits' => [
                    'per_minute' => $apiKey->rate_limit_per_minute,
                    'per_day' => $apiKey->rate_limit_per_day
                ]
            ]
        ]);
    }

    /**
     * Get balance
     */
    public function getBalance(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (float) $user->balance,
                'currency' => 'NGN'
            ]
        ]);
    }

    /**
     * Purchase SMS number
     */
    public function purchaseSms(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'country_code' => 'required|string',
            'service' => 'required|string',
            'provider' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create SMS order (same logic as web interface)
            $provider = $request->provider ?? 'tiger_sms';
            $smsService = \App\Models\SmsService::where('provider', $provider)
                ->where('is_active', true)
                ->first();

            if (!$smsService) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not available',
                    'message' => 'The specified SMS provider is not available'
                ], 400);
            }

            // Get service price
            $services = $this->smsProviderService->getServices($smsService, $request->country_code);
            $serviceData = collect($services)->firstWhere('service', $request->service);

            if (!$serviceData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Service not found',
                    'message' => 'The specified service is not available for this country'
                ], 404);
            }

            $cost = $serviceData['cost'] ?? 0;

            if ($user->balance < $cost) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance',
                    'message' => 'Your account balance is insufficient for this purchase',
                    'required' => $cost,
                    'available' => (float) $user->balance
                ], 400);
            }

            // Create order with provider
            $result = $this->smsProviderService->createOrder(
                $smsService,
                $request->country_code,
                $request->service
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order creation failed',
                    'message' => $result['message'] ?? 'Failed to create SMS order'
                ], 400);
            }

            // Deduct balance
            $user->updateBalance($cost, 'subtract');

            // Save order to database
            $order = SmsOrder::create([
                'user_id' => $user->id,
                'sms_service_id' => $smsService->id,
                'order_id' => 'SMS_' . \Str::random(10),
                'provider_order_id' => $result['order_id'],
                'country_code' => $request->country_code,
                'service' => $request->service,
                'phone_number' => $result['phone_number'] ?? null,
                'cost' => $cost,
                'status' => 'active',
                'provider' => $provider,
                'expires_at' => now()->addMinutes(20)
            ]);

            // Record transaction
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => 'service_purchase',
                'amount' => $cost,
                'balance_before' => $user->balance + $cost,
                'balance_after' => $user->balance,
                'description' => "SMS number: {$request->service} ({$request->country_code}) via API",
                'reference' => $order->order_id,
                'status' => 'success',
                'metadata' => json_encode([
                    'category' => 'sms',
                    'provider' => $provider,
                    'country_code' => $request->country_code,
                    'service' => $request->service,
                    'api_purchase' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->order_id,
                    'phone_number' => $result['phone_number'],
                    'country_code' => $request->country_code,
                    'service' => $request->service,
                    'cost' => $cost,
                    'balance' => (float) $user->balance,
                    'expires_at' => $order->expires_at->toIso8601String(),
                    'status' => 'active'
                ],
                'message' => 'SMS number purchased successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('API SMS purchase failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS code for an order
     */
    public function getSmsCode(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = SmsOrder::where('order_id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
                'message' => 'The specified order was not found'
            ], 404);
        }

        if ($order->sms_code) {
            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->order_id,
                    'phone_number' => $order->phone_number,
                    'sms_code' => $order->sms_code,
                    'status' => $order->status,
                    'received_at' => $order->received_at?->toIso8601String()
                ]
            ]);
        }

        // Query provider for code
        try {
            $smsCode = $this->smsProviderService->getSmsCode($order->smsService, $order->provider_order_id);

            if ($smsCode) {
                $order->markAsCompleted($smsCode);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_id' => $order->order_id,
                        'phone_number' => $order->phone_number,
                        'sms_code' => $smsCode,
                        'status' => 'completed',
                        'received_at' => now()->toIso8601String()
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->order_id,
                    'phone_number' => $order->phone_number,
                    'sms_code' => null,
                    'status' => 'waiting',
                    'message' => 'No SMS code received yet'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get SMS code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase airtime
     */
    public function purchaseAirtime(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'network' => 'required|string|in:mtn,airtel,glo,9mobile',
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:50|max:50000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $amount = (float) $request->amount;

            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance',
                    'message' => 'Your account balance is insufficient',
                    'required' => $amount,
                    'available' => (float) $user->balance
                ], 400);
            }

            $reference = 'AIR_' . \Str::random(10);
            $result = $this->vtuService->purchaseAirtime(
                $request->network,
                $request->phone,
                $amount,
                $reference
            );

            if ($result['success']) {
                $user->updateBalance($amount, 'subtract');

                // Record transaction
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Airtime: {$request->network} ({$request->phone}) via API",
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => json_encode([
                        'category' => 'airtime',
                        'network' => $request->network,
                        'phone' => $request->phone,
                        'api_purchase' => true
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'network' => $request->network,
                        'phone' => $request->phone,
                        'amount' => $amount,
                        'balance' => (float) $user->balance,
                        'status' => 'success'
                    ],
                    'message' => 'Airtime purchased successfully'
                ], 201);
            }

            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $result['message'] ?? 'Airtime purchase failed'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase data bundle
     */
    public function purchaseData(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'network' => 'required|string',
            'phone' => 'required|string',
            'plan_id' => 'required|string',
            'amount' => 'required|numeric|min:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $amount = (float) $request->amount;

            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance',
                    'required' => $amount,
                    'available' => (float) $user->balance
                ], 400);
            }

            $reference = 'DATA_' . \Str::random(10);
            $result = $this->vtuService->purchaseDataBundle(
                $request->network,
                $request->phone,
                $request->plan_id,
                $amount,
                $reference
            );

            if ($result['success']) {
                $user->updateBalance($amount, 'subtract');

                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Data: {$request->network} ({$request->phone}) via API",
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => json_encode([
                        'category' => 'data',
                        'network' => $request->network,
                        'phone' => $request->phone,
                        'plan_id' => $request->plan_id,
                        'api_purchase' => true
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'network' => $request->network,
                        'phone' => $request->phone,
                        'plan_id' => $request->plan_id,
                        'amount' => $amount,
                        'balance' => (float) $user->balance,
                        'status' => 'success'
                    ],
                    'message' => 'Data bundle purchased successfully'
                ], 201);
            }

            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $result['message'] ?? 'Data purchase failed'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase electricity
     */
    public function purchaseElectricity(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'disco' => 'required|string',
            'meter_number' => 'required|string',
            'meter_type' => 'required|string|in:prepaid,postpaid',
            'amount' => 'required|numeric|min:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $amount = (float) $request->amount;

            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient balance',
                    'required' => $amount,
                    'available' => (float) $user->balance
                ], 400);
            }

            $reference = 'ELEC_' . \Str::random(10);
            $result = $this->vtuService->purchaseElectricity(
                $request->disco,
                $request->meter_number,
                $request->meter_type,
                $amount,
                $reference
            );

            if ($result['success']) {
                $tokenData = $result['data'] ?? [];
                $user->updateBalance($amount, 'subtract');

                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Electricity: {$request->disco} ({$request->meter_number}) via API",
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => json_encode([
                        'category' => 'electricity',
                        'disco' => $request->disco,
                        'meter_number' => $request->meter_number,
                        'meter_type' => $request->meter_type,
                        'token' => $tokenData['token'] ?? null,
                        'api_purchase' => true
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'disco' => $request->disco,
                        'meter_number' => $request->meter_number,
                        'meter_type' => $request->meter_type,
                        'amount' => $amount,
                        'token' => $tokenData['token'] ?? null,
                        'customer_name' => $tokenData['customer_name'] ?? null,
                        'balance' => (float) $user->balance,
                        'status' => 'success'
                    ],
                    'message' => 'Electricity purchased successfully'
                ], 201);
            }

            // Handle processing status
            if (!empty($result['processing'])) {
                $user->updateBalance($amount, 'subtract');

                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Electricity: {$request->disco} ({$request->meter_number}) - PROCESSING via API",
                    'reference' => $reference,
                    'status' => 'pending',
                    'metadata' => json_encode([
                        'category' => 'electricity',
                        'disco' => $request->disco,
                        'meter_number' => $request->meter_number,
                        'meter_type' => $request->meter_type,
                        'api_purchase' => true,
                        'needs_status_check' => true
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'processing' => true,
                    'data' => [
                        'reference' => $reference,
                        'status' => 'processing',
                        'amount' => $amount,
                        'balance' => (float) $user->balance,
                        'message' => 'Request sent to provider, token will be delivered when ready'
                    ],
                    'message' => 'Electricity purchase is processing. Check status in 5-10 minutes.'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $result['message'] ?? 'Electricity purchase failed'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $limit = min((int) $request->input('limit', 50), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $limit;

        $transactions = DB::table('transactions')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $total = DB::table('transactions')->where('user_id', $user->id)->count();

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get transaction by reference
     */
    public function getTransaction(Request $request, string $reference): JsonResponse
    {
        $user = $request->attributes->get('api_user');

        $transaction = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('reference', $reference)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }
}



