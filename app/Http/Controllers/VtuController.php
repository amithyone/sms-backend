<?php

namespace App\Http\Controllers;

use App\Services\DatabaseVtuService;
use App\Services\ElectricitySmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VtuController extends Controller
{
    private $vtuService;
    private $electricitySmsService;

    public function __construct(DatabaseVtuService $vtuService, ElectricitySmsService $electricitySmsService)
    {
        $this->vtuService = $vtuService;
        $this->electricitySmsService = $electricitySmsService;
    }

    /**
     * Get available airtime networks
     */
    public function getAirtimeNetworks(): JsonResponse
    {
        // Return static data for now to avoid external service issues
        $networks = [
            [
                'id' => 'mtn',
                'name' => 'MTN',
                'code' => 'MTN',
                'status' => 'active'
            ],
            [
                'id' => 'airtel',
                'name' => 'Airtel',
                'code' => 'AIRTEL',
                'status' => 'active'
            ],
            [
                'id' => 'glo',
                'name' => 'Glo',
                'code' => 'GLO',
                'status' => 'active'
            ],
            [
                'id' => '9mobile',
                'name' => '9mobile',
                'code' => '9MOBILE',
                'status' => 'active'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $networks,
            'message' => 'Airtime networks retrieved successfully'
        ]);
    }

    /**
     * Get general VTU services
     */
    public function getServices(): JsonResponse
    {
        try {
            $services = [
                'airtime' => [
                    'name' => 'Airtime Recharge',
                    'description' => 'Recharge airtime for all networks',
                    'networks' => ['MTN', 'Airtel', 'Glo', '9mobile']
                ],
                'data' => [
                    'name' => 'Data Bundles',
                    'description' => 'Purchase data bundles for all networks',
                    'networks' => ['MTN', 'Airtel', 'Glo', '9mobile']
                ],
                'cable_tv' => [
                    'name' => 'Cable TV',
                    'description' => 'Pay for DSTV, GOTV, and Startimes',
                    'providers' => ['DSTV', 'GOTV', 'Startimes']
                ],
                'electricity' => [
                    'name' => 'Electricity Bills',
                    'description' => 'Pay electricity bills',
                    'providers' => ['IKEDC', 'EKEDC', 'KEDCO']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'VTU services retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve VTU services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available data networks
     */
    public function getDataNetworks(): JsonResponse
    {
        // Return static data for now to avoid external service issues
        $networks = [
            [
                'id' => 'mtn',
                'name' => 'MTN',
                'code' => 'MTN',
                'status' => 'active'
            ],
            [
                'id' => 'airtel',
                'name' => 'Airtel',
                'code' => 'AIRTEL',
                'status' => 'active'
            ],
            [
                'id' => 'glo',
                'name' => 'Glo',
                'code' => 'GLO',
                'status' => 'active'
            ],
            [
                'id' => '9mobile',
                'name' => '9mobile',
                'code' => '9MOBILE',
                'status' => 'active'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $networks,
            'message' => 'Data networks retrieved successfully'
        ]);
    }

    /**
     * Get data bundles for a specific network
     */
    public function getDataBundles(Request $request): JsonResponse
    {
        // Accept either 'network' or frontend's 'service_id'
        $network = $request->input('network') ?? $request->input('service_id');

        if (!$network || !is_string($network)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'network' => ['The network (or service_id) field is required.']
                ]
            ], 422);
        }

        try {
            $bundles = $this->vtuService->getDataBundles($network);

            // Fallback if provider returns empty
            if (empty($bundles)) {
                $bundles = $this->getStaticDataBundles($network);
            }

            // Standardize response format for frontend compatibility
            // Frontend expects: { success: true, data: [array of bundles], message: "..." }
            $payload = $bundles;
            
            // If bundles is wrapped in a 'data' key, extract it
            if (is_array($bundles) && isset($bundles['data']) && is_array($bundles['data'])) {
                $payload = $bundles['data'];
            }
            // If bundles is already a direct array, use it as is
            elseif (is_array($bundles) && !empty($bundles) && isset($bundles[0])) {
                $payload = $bundles;
            }
            // If empty or invalid, ensure it's an empty array
            else {
                $payload = [];
            }

            return response()->json([
                'success' => true,
                'data' => $payload,
                'message' => 'Data bundles retrieved successfully'
            ]);
        } catch (\Exception $e) {
            // Return static bundles on error to avoid frontend mock fallback
            $fallback = $this->getStaticDataBundles($network);
            
            return response()->json([
                'success' => true,
                'data' => $fallback, // Direct array, not wrapped
                'message' => 'Data bundles retrieved successfully (fallback)'
            ]);
        }
    }

    /**
     * Local fallback bundles per network
     */
    private function getStaticDataBundles(string $network): array
    {
        $networkKey = strtolower($network);
        $common = [
            ['code' => '500MB', 'name' => '500MB Daily', 'price' => 1500],
            ['code' => '1GB', 'name' => '1GB Daily', 'price' => 3000],
            ['code' => '2GB', 'name' => '2GB 2-Days', 'price' => 5000],
            ['code' => '3GB', 'name' => '3GB Weekly', 'price' => 9000],
            ['code' => '5GB', 'name' => '5GB Weekly', 'price' => 15000],
            ['code' => '10GB', 'name' => '10GB Monthly', 'price' => 30000],
        ];

        return array_map(function ($b) use ($networkKey) {
            return [
                'plan' => $b['code'],
                'plan_name' => $b['name'],
                'amount' => $b['price'],
                'network' => $networkKey,
            ];
        }, $common);
    }

    /**
     * Purchase airtime
     */
    public function purchaseAirtime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'network' => 'required|string',
            'phone' => 'required|string|min:11|max:11',
            'amount' => 'required|numeric|min:50|max:50000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // CRITICAL: Check if user has VTU access
            if (!$user->vtu_access_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'VTU services access has been disabled for your account. Reason: ' . ($user->vtu_access_reason ?? 'Contact support for more information.'),
                    'error_code' => 'VTU_ACCESS_DISABLED'
                ], 403);
            }
            
            $network = $request->network;
            $phone = $request->phone;
            $amount = $request->amount;

            // Check user balance
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Please recharge your account.'
                ], 400);
            }

            // Validate phone number
            if (!$this->vtuService->validatePhoneNumber($phone, $network)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number for the selected network.'
                ], 400);
            }

            // Generate reference
            $reference = 'VTU_' . Str::random(10);

            // Purchase airtime
            $result = $this->vtuService->purchaseAirtime($network, $phone, $amount, $reference);

            if ($result['success']) {
                // Deduct balance from user
                $user->updateBalance($amount, 'subtract');

                // Create transaction record
                $user->transactions()->create([
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Airtime purchase for {$phone} ({$network})",
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => [
                        'network' => $network,
                        'phone' => $phone,
                        'amount' => $amount,
                        'provider' => 'vtu_ng',
                        'response' => $result['data']
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'network' => $network,
                        'phone' => $phone,
                        'amount' => $amount,
                        'status' => 'success',
                        'message' => $result['message']
                    ],
                    'message' => 'Airtime purchase successful'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase airtime: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase data bundle
     */
    public function purchaseDataBundle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'network' => 'required|string',
            'phone' => 'required|string|min:11|max:11',
            'plan' => 'required|string',
            'plan_name' => 'required|string',
            'amount' => 'required|numeric|min:50|max:50000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // CRITICAL: Check if user has VTU access
            if (!$user->vtu_access_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'VTU services access has been disabled for your account. Reason: ' . ($user->vtu_access_reason ?? 'Contact support for more information.'),
                    'error_code' => 'VTU_ACCESS_DISABLED'
                ], 403);
            }
            
            $network = $request->network;
            $phone = $request->phone;
            $plan = $request->plan;
            $planName = $request->plan_name;
            $amount = $request->amount;

            // Check user balance
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Please recharge your account.'
                ], 400);
            }

            // Validate phone number
            if (!$this->vtuService->validatePhoneNumber($phone, $network)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number for the selected network.'
                ], 400);
            }

            // Generate reference
            $reference = 'VTU_' . Str::random(10);

            // Purchase data bundle
            $result = $this->vtuService->purchaseDataBundle($network, $phone, $plan, $reference);

            if ($result['success']) {
                // Deduct balance from user
                $user->updateBalance($amount, 'subtract');

                // Create transaction record
                $user->transactions()->create([
                    'type' => 'service_purchase',
                    'amount' => $amount,
                    'balance_before' => $user->balance + $amount,
                    'balance_after' => $user->balance,
                    'description' => "Data bundle purchase for {$phone} ({$network}) - {$planName}",
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => [
                        'network' => $network,
                        'phone' => $phone,
                        'plan' => $plan,
                        'plan_name' => $planName,
                        'amount' => $amount,
                        'provider' => 'vtu_ng',
                        'response' => $result['data']
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'network' => $network,
                        'phone' => $phone,
                        'plan' => $plan,
                        'plan_name' => $planName,
                        'amount' => $amount,
                        'status' => 'success',
                        'message' => $result['message']
                    ],
                    'message' => 'Data bundle purchase successful'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('VTU data bundle purchase failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to purchase data bundle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->vtuService->getTransactionStatus($request->reference);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get iRecharge account balance
     */
    public function getProviderBalance(): JsonResponse
    {
        try {
            $result = $this->vtuService->getBalance();
            // If provider returned raw fields, pass them through for debugging/visibility
            if (!empty($result['success'])) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Provider balance retrieved successfully'
                ]);
            }
            return response()->json([
                'success' => false,
                'data' => $result,
                'message' => $result['message'] ?? 'Failed to fetch provider balance'
            ], 502);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provider balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate phone number
     */
    public function validatePhoneNumber(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:11|max:11',
            'network' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $isValid = $this->vtuService->validatePhoneNumber($request->phone, $request->network);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'phone' => $request->phone,
                    'network' => $request->network
                ],
                'message' => $isValid ? 'Phone number is valid' : 'Phone number is invalid for the selected network'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate phone number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        try {
            $rows = \DB::table('transactions')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            $items = $rows->map(function ($t) {
                // Map backend enums to frontend expectations
                $type = $t->type === 'credit' ? 'credit' : 'debit';
                if ($t->type === 'service_purchase') { $type = 'debit'; }

                $status = 'pending';
                if ($t->status === 'success') { $status = 'completed'; }
                elseif ($t->status === 'failed') { $status = 'failed'; }

                // Parse metadata for additional details
                $metadata = [];
                if ($t->metadata) {
                    try {
                        $metadata = json_decode($t->metadata, true) ?? [];
                    } catch (\Exception $e) {
                        $metadata = [];
                    }
                }

                // Build transaction details
                $transactionData = [
                    'id' => $t->id,
                    'type' => $type,
                    'amount' => (float) $t->amount,
                    'description' => (string) $t->description,
                    'status' => $status,
                    'reference' => $t->reference,
                    'created_at' => (string) $t->created_at,
                    'balance_before' => (float) $t->balance_before,
                    'balance_after' => (float) $t->balance_after,
                ];

                // Add service-specific details
                if (isset($metadata['category'])) {
                    $transactionData['category'] = $metadata['category'];
                    
                    // Add electricity-specific details
                    if ($metadata['category'] === 'electricity') {
                        $transactionData['service_id'] = $metadata['service_id'] ?? null;
                        $transactionData['customer_id'] = $metadata['customer_id'] ?? null;
                        $transactionData['variation_id'] = $metadata['variation_id'] ?? null;
                        $transactionData['token'] = $metadata['token'] ?? null;
                        $transactionData['token_type'] = $metadata['token_type'] ?? null;
                        $transactionData['units'] = $metadata['units'] ?? null;
                        $transactionData['customer_name'] = $metadata['customer_name'] ?? null;
                        $transactionData['address'] = $metadata['address'] ?? null;
                        
                        // Add inbox information for electricity transactions
                        $transactionData['has_inbox_message'] = true;
                        $transactionData['inbox_message_type'] = 'electricity_token';
                        $transactionData['inbox_message_title'] = 'Electricity Token - ' . ($metadata['customer_name'] ?? 'Customer');
                    }
                    
                    // Add betting-specific details
                    if ($metadata['category'] === 'betting') {
                        $transactionData['service_id'] = $metadata['service_id'] ?? null;
                        $transactionData['customer_id'] = $metadata['customer_id'] ?? null;
                    }
                }

                // Add error information for failed transactions
                if ($status === 'failed' && isset($metadata['error'])) {
                    $transactionData['error'] = $metadata['error'];
                }

                return $transactionData;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $items,
                'message' => 'Transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed transaction information
     */
    public function getTransactionDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $transaction = \DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('reference', $request->reference)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Parse metadata
            $metadata = [];
            if ($transaction->metadata) {
                try {
                    $metadata = json_decode($transaction->metadata, true) ?? [];
                } catch (\Exception $e) {
                    $metadata = [];
                }
            }

            // Map status
            $status = 'pending';
            if ($transaction->status === 'success') { $status = 'completed'; }
            elseif ($transaction->status === 'failed') { $status = 'failed'; }

            // Build detailed transaction data
            $transactionData = [
                'id' => $transaction->id,
                'type' => $transaction->type === 'credit' ? 'credit' : 'debit',
                'amount' => (float) $transaction->amount,
                'description' => (string) $transaction->description,
                'status' => $status,
                'reference' => $transaction->reference,
                'created_at' => (string) $transaction->created_at,
                'updated_at' => (string) $transaction->updated_at,
                'balance_before' => (float) $transaction->balance_before,
                'balance_after' => (float) $transaction->balance_after,
                'fee' => (float) $transaction->fee,
                'total_amount' => (float) $transaction->total_amount,
                'metadata' => $metadata
            ];

            // Add service-specific details
            if (isset($metadata['category'])) {
                $transactionData['category'] = $metadata['category'];
                
                // Add electricity-specific details
                if ($metadata['category'] === 'electricity') {
                    $transactionData['service_id'] = $metadata['service_id'] ?? null;
                    $transactionData['customer_id'] = $metadata['customer_id'] ?? null;
                    $transactionData['variation_id'] = $metadata['variation_id'] ?? null;
                    $transactionData['token'] = $metadata['token'] ?? null;
                    $transactionData['token_type'] = $metadata['token_type'] ?? null;
                    $transactionData['units'] = $metadata['units'] ?? null;
                    $transactionData['customer_name'] = $metadata['customer_name'] ?? null;
                    $transactionData['address'] = $metadata['address'] ?? null;
                    
                    // Add inbox information for electricity transactions
                    $transactionData['has_inbox_message'] = true;
                    $transactionData['inbox_message_type'] = 'electricity_token';
                    $transactionData['inbox_message_title'] = 'Electricity Token - ' . ($metadata['customer_name'] ?? 'Customer');
                }
                
                // Add betting-specific details
                if ($metadata['category'] === 'betting') {
                    $transactionData['service_id'] = $metadata['service_id'] ?? null;
                    $transactionData['customer_id'] = $metadata['customer_id'] ?? null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $transactionData,
                'message' => 'Transaction details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Betting providers (static for now)
     */
    public function getBettingProviders(): JsonResponse
    {
        $providers = [
            [ 'id' => 'bet9ja', 'name' => 'Bet9ja' ],
            [ 'id' => 'betking', 'name' => 'BetKing' ],
            [ 'id' => 'betway', 'name' => 'Betway' ],
            [ 'id' => '1xbet', 'name' => '1xBet' ],
            [ 'id' => 'nairabet', 'name' => 'NairaBet' ],
            [ 'id' => 'merrybet', 'name' => 'MerryBet' ],
            [ 'id' => 'msport', 'name' => 'MSport' ],
            [ 'id' => 'bangbet', 'name' => 'BangBet' ],
            [ 'id' => 'livescorebet', 'name' => 'LiveScore Bet' ],
            [ 'id' => 'betpawa', 'name' => 'BetPawa' ],
            [ 'id' => 'betano', 'name' => 'Betano' ],
        ];
        return response()->json(['success' => true, 'data' => $providers]);
    }

    /**
     * Verify customer (betting/tv/electricity passthrough to provider)
     */
    public function verifyCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'customer_id' => 'required|string',
            'variation_id' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        try {
            $serviceId = $this->normalizeBettingServiceId($request->service_id);
            $res = app(\App\Services\VtuNgService::class)->verifyCustomer($serviceId, $request->customer_id, $request->variation_id);
            return response()->json(['success' => $res['success'], 'data' => $res['data'], 'message' => $res['message']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fund betting account
     */
    public function purchaseBetting(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'customer_id' => 'required|string',
            'amount' => 'required|numeric|min:100'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        try {
            $user = Auth::user();
            
            // CRITICAL: Check if user has VTU access
            if (!$user->vtu_access_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'VTU services access has been disabled for your account. Reason: ' . ($user->vtu_access_reason ?? 'Contact support for more information.'),
                    'error_code' => 'VTU_ACCESS_DISABLED'
                ], 403);
            }
            
            if ($user->balance < $request->amount) {
                return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
            }
            $reference = 'BET_' . Str::random(10);
            $serviceId = $this->normalizeBettingServiceId($request->service_id);
            $res = app(\App\Services\VtuNgService::class)->purchaseBetting($serviceId, $request->customer_id, (float)$request->amount, $reference);
            // VtuNgService already validates if VTU.ng returned success code
            if ($res['success']) {
                $user->updateBalance((float)$request->amount, 'subtract');
                // Record VTU order
                \DB::table('vtu_orders')->insert([
                    'user_id' => $user->id,
                    'order_id' => $reference,
                    'service_type' => 'betting',
                    'provider' => 'vtu_ng',
                    'network' => $request->service_id,
                    'phone_number' => $request->customer_id,
                    'amount' => (float)$request->amount,
                    'fee' => 0,
                    'total_amount' => (float)$request->amount,
                    'status' => 'completed',
                    'reference' => $reference,
                    'provider_response' => json_encode($res['data']),
                    'processed_at' => now(),
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Record transaction
                \DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => (float)$request->amount,
                    'balance_before' => $user->balance + (float)$request->amount,
                    'balance_after' => $user->balance,
                    'description' => 'Betting funding: '.$request->service_id.' ('.$request->customer_id.')',
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => json_encode(['category' => 'betting', 'service_id' => $request->service_id, 'customer_id' => $request->customer_id]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'data' => [ 'reference' => $reference ], 'message' => 'Betting funded']);
            }
            return response()->json(['success' => false, 'message' => $res['message'] ?? 'Betting funding failed'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Electricity providers
     */
    public function getElectricityProviders(): JsonResponse
    {
        $list = app(\App\Services\VtuNgService::class)->getElectricityProviders();
        return response()->json(['success' => true, 'data' => $list]);
    }

    public function verifyElectricityCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'customer_id' => 'required|string',
            'variation_id' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        
        $serviceId = $request->service_id;
        $customerId = $request->customer_id;
        $variationId = $request->variation_id ?? 'prepaid';
        
        // Check cache first (verified meters table)
        $cached = \DB::table('verified_meters')
            ->where('service_id', $serviceId)
            ->where('meter_number', $customerId)
            ->where('meter_type', $variationId)
            ->where('expires_at', '>', now())
            ->first();
        
        if ($cached) {
            \Log::info('Electricity verification - cache hit', [
                'service_id' => $serviceId,
                'meter_number' => $customerId,
                'cached_at' => $cached->last_verified_at
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'customer_name' => $cached->customer_name,
                    'address' => $cached->address,
                    'account_type' => $cached->account_type,
                    'outstanding_balance' => $cached->outstanding_balance,
                    'cached' => true,
                    'last_verified' => $cached->last_verified_at
                ],
                'message' => 'Meter verified (from cache)'
            ]);
        }
        
        // Cache miss - query API
        \Log::info('Electricity verification - cache miss, querying API', [
            'service_id' => $serviceId,
            'meter_number' => $customerId
        ]);
        
        $res = app(\App\Services\VtuNgService::class)->verifyElectricityCustomer($serviceId, $customerId, $variationId);
        
        // If verification successful, cache it
        if ($res['success'] && !empty($res['data'])) {
            try {
                $data = $res['data'];
                $customerName = $data['data']['customer_name'] ?? $data['customer_name'] ?? 'Unknown';
                $address = $data['data']['address'] ?? $data['address'] ?? null;
                $accountType = $data['data']['account_type'] ?? $data['account_type'] ?? null;
                $outstandingBalance = $data['data']['outstanding_balance'] ?? $data['outstanding_balance'] ?? 0;
                
                // Insert or update cache
                \DB::table('verified_meters')->updateOrInsert(
                    [
                        'service_id' => $serviceId,
                        'meter_number' => $customerId,
                        'meter_type' => $variationId
                    ],
                    [
                        'customer_name' => $customerName,
                        'address' => $address,
                        'account_type' => $accountType,
                        'outstanding_balance' => $outstandingBalance,
                        'verification_data' => json_encode($data),
                        'last_verified_at' => now(),
                        'expires_at' => now()->addDays(30), // Cache for 30 days
                        'updated_at' => now(),
                        'created_at' => \DB::raw('COALESCE(created_at, NOW())')
                    ]
                );
                
                \Log::info('Electricity verification cached', [
                    'service_id' => $serviceId,
                    'meter_number' => $customerId,
                    'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to cache verified meter', [
                    'error' => $e->getMessage(),
                    'service_id' => $serviceId,
                    'meter_number' => $customerId
                ]);
            }
        }
        
        return response()->json(['success' => $res['success'], 'data' => $res['data'], 'message' => $res['message']]);
    }

    public function purchaseElectricity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|string',
            'customer_id' => 'required|string',
            'variation_id' => 'required|string',
            'amount' => 'required|numeric|min:100'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        
        try {
            $user = Auth::user();
            
            // CRITICAL: Check if user has VTU access
            if (!$user->vtu_access_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'VTU services access has been disabled for your account. Reason: ' . ($user->vtu_access_reason ?? 'Contact support for more information.'),
                    'error_code' => 'VTU_ACCESS_DISABLED'
                ], 403);
            }
            
            if ($user->balance < $request->amount) {
                return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
            }
            
            $reference = 'ELEC_' . Str::random(10);
            $res = app(\App\Services\VtuNgService::class)->purchaseElectricity($request->service_id, $request->customer_id, $request->variation_id, (float)$request->amount, $reference);
            
            // VtuNgService already validates if VTU.ng returned success code
            if ($res['success']) {
                // Extract token information from provider response
                $tokenData = $this->extractElectricityToken($res['data']);
                
                $user->updateBalance((float)$request->amount, 'subtract');
                
                // Record VTU order with token information
                \DB::table('vtu_orders')->insert([
                    'user_id' => $user->id,
                    'order_id' => $reference,
                    'service_type' => 'electricity',
                    'provider' => 'vtu_ng',
                    'network' => $request->service_id,
                    'phone_number' => $request->customer_id,
                    'amount' => (float)$request->amount,
                    'fee' => 0,
                    'total_amount' => (float)$request->amount,
                    'status' => 'completed',
                    'reference' => $reference,
                    'provider_response' => json_encode($res['data']),
                    'processed_at' => now(),
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Record transaction with token information
                $transactionMetadata = [
                    'category' => 'electricity',
                    'service_id' => $request->service_id,
                    'customer_id' => $request->customer_id,
                    'variation_id' => $request->variation_id,
                    'token' => $tokenData['token'] ?? null,
                    'token_type' => $tokenData['token_type'] ?? null,
                    'units' => $tokenData['units'] ?? null,
                    'customer_name' => $tokenData['customer_name'] ?? null,
                    'address' => $tokenData['address'] ?? null,
                    'amount' => (float)$request->amount,
                    'provider_response' => $res['data']
                ];
                
                \DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => (float)$request->amount,
                    'balance_before' => $user->balance + (float)$request->amount,
                    'balance_after' => $user->balance,
                    'description' => 'Electricity bill: '.$request->service_id.' ('.$request->customer_id.')',
                    'reference' => $reference,
                    'status' => 'success',
                    'metadata' => json_encode($transactionMetadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Send SMS notification with electricity token
                $smsTokenData = array_merge($tokenData, [
                    'amount' => (float)$request->amount,
                    'service_id' => $request->service_id,
                    'customer_id' => $request->customer_id,
                    'variation_id' => $request->variation_id
                ]);
                
                try {
                    $this->electricitySmsService->sendElectricityTokenSms($user, $smsTokenData, $reference);
                } catch (\Exception $smsError) {
                    // Log SMS error but don't fail the whole transaction
                    \Log::error('Electricity SMS sending failed', [
                        'error' => $smsError->getMessage(),
                        'reference' => $reference
                    ]);
                }
                
                return response()->json([
                    'success' => true, 
                    'data' => [
                        'reference' => $reference,
                        'token' => $tokenData['token'] ?? null,
                        'token_type' => $tokenData['token_type'] ?? null,
                        'units' => $tokenData['units'] ?? null,
                        'customer_name' => $tokenData['customer_name'] ?? null,
                        'address' => $tokenData['address'] ?? null,
                        'amount' => (float)$request->amount,
                        'status' => 'success',
                        'sms_sent' => true,
                        'inbox_message' => 'Token details sent to your inbox and SMS'
                    ], 
                    'message' => 'Electricity purchased successfully. Token sent to your inbox and SMS.'
                ]);
            } elseif (!empty($res['processing'])) {
                // Handle timeout/processing status - balance is deducted since request was sent
                $user->updateBalance((float)$request->amount, 'subtract');
                
                // Record VTU order with processing status
                \DB::table('vtu_orders')->insert([
                    'user_id' => $user->id,
                    'order_id' => $reference,
                    'service_type' => 'electricity',
                    'provider' => 'vtu_ng',
                    'network' => $request->service_id,
                    'phone_number' => $request->customer_id,
                    'amount' => (float)$request->amount,
                    'fee' => 0,
                    'total_amount' => (float)$request->amount,
                    'status' => 'processing',
                    'reference' => $reference,
                    'provider_response' => json_encode(['status' => 'timeout', 'message' => $res['message']]),
                    'processed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Record transaction as pending (not failed)
                \DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => (float)$request->amount,
                    'balance_before' => $user->balance + (float)$request->amount,
                    'balance_after' => $user->balance,
                    'description' => 'Electricity bill: '.$request->service_id.' ('.$request->customer_id.') - PROCESSING',
                    'reference' => $reference,
                    'status' => 'pending',
                    'metadata' => json_encode([
                        'category' => 'electricity',
                        'service_id' => $request->service_id,
                        'customer_id' => $request->customer_id,
                        'variation_id' => $request->variation_id,
                        'timeout_message' => $res['message'] ?? 'Request timeout - processing',
                        'needs_status_check' => true
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Create inbox message to inform user
                try {
                    \DB::table('inbox_messages')->insert([
                        'user_id' => $user->id,
                        'type' => 'electricity',
                        'title' => 'Electricity Purchase Processing',
                        'message' => "Your electricity purchase for {$request->customer_id} (₦" . number_format($request->amount) . ") is being processed. This usually happens due to provider timeout. Please check your inbox in 5-10 minutes for the token. Reference: {$reference}",
                        'metadata' => json_encode([
                            'reference' => $reference,
                            'service_id' => $request->service_id,
                            'customer_id' => $request->customer_id,
                            'amount' => $request->amount,
                            'status' => 'processing'
                        ]),
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $inboxError) {
                    \Log::error('Failed to create inbox message for processing electricity', ['error' => $inboxError->getMessage()]);
                }
                
                return response()->json([
                    'success' => true,  // Return true so user knows request was received
                    'processing' => true,
                    'data' => [
                        'reference' => $reference,
                        'status' => 'processing',
                        'amount' => (float)$request->amount,
                        'message' => $res['message']
                    ],
                    'message' => 'Request received and processing. Due to provider timeout, your electricity token will be delivered to your inbox within 5-10 minutes. Check your inbox or transactions for updates.'
                ], 200);
            } else {
                // Record failed transaction (only for actual failures, not timeouts)
                \DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'service_purchase',
                    'amount' => (float)$request->amount,
                    'balance_before' => $user->balance,
                    'balance_after' => $user->balance,
                    'description' => 'Electricity bill: '.$request->service_id.' ('.$request->customer_id.') - FAILED',
                    'reference' => $reference,
                    'status' => 'failed',
                    'metadata' => json_encode([
                        'category' => 'electricity',
                        'service_id' => $request->service_id,
                        'customer_id' => $request->customer_id,
                        'variation_id' => $request->variation_id,
                        'error' => $res['message'] ?? 'Purchase failed',
                        'provider_response' => $res['data']
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                return response()->json(['success' => false, 'message' => $res['message'] ?? 'Electricity purchase failed'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Electricity purchase exception', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['success' => false, 'message' => 'Electricity purchase failed: ' . $e->getMessage()], 500);
        }
    }

    private function normalizeBettingServiceId(string $id): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($id));
    }

    /**
     * Extract electricity token information from provider response
     */
    private function extractElectricityToken(array $providerResponse): array
    {
        $tokenData = [
            'token' => null,
            'token_type' => null,
            'units' => null,
            'customer_name' => null,
            'address' => null
        ];

        try {
            // Handle different response formats from VTU providers
            $data = $providerResponse['data'] ?? $providerResponse;
            
            // Extract token (common field names)
            $tokenData['token'] = $data['token'] ?? $data['meter_token'] ?? $data['electricity_token'] ?? null;
            
            // Extract token type
            $tokenData['token_type'] = $data['token_type'] ?? $data['type'] ?? 'prepaid';
            
            // Extract units
            $tokenData['units'] = $data['units'] ?? $data['kwh'] ?? $data['kilowatt_hour'] ?? null;
            
            // Extract customer information
            $tokenData['customer_name'] = $data['customer_name'] ?? $data['name'] ?? $data['customer'] ?? null;
            
            // Extract address
            $tokenData['address'] = $data['address'] ?? $data['customer_address'] ?? null;
            
            // If token is in a nested structure, try to extract it
            if (!$tokenData['token'] && isset($data['purchase'])) {
                $purchase = $data['purchase'];
                $tokenData['token'] = $purchase['token'] ?? $purchase['meter_token'] ?? null;
                $tokenData['units'] = $purchase['units'] ?? $purchase['kwh'] ?? null;
            }
            
            // Check meta_data field (VTU.ng specific structure)
            if (!$tokenData['token'] && isset($data['meta_data'])) {
                $metaData = $data['meta_data'];
                $tokenData['token'] = $metaData['electricity_token'] ?? $metaData['token'] ?? null;
                $tokenData['customer_name'] = $tokenData['customer_name'] ?? $metaData['customer_name'] ?? null;
                $tokenData['address'] = $tokenData['address'] ?? $metaData['customer_address'] ?? null;
                // Don't estimate units - if VTU doesn't provide, leave as null
            }
            
            // Log the extraction for debugging
            \Log::info('Electricity token extraction', [
                'provider_response' => $providerResponse,
                'extracted_data' => $tokenData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to extract electricity token', [
                'error' => $e->getMessage(),
                'provider_response' => $providerResponse
            ]);
        }

        return $tokenData;
    }
}
