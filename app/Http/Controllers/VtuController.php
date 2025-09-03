<?php

namespace App\Http\Controllers;

use App\Services\DatabaseVtuService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VtuController extends Controller
{
    private $vtuService;

    public function __construct(DatabaseVtuService $vtuService)
    {
        $this->vtuService = $vtuService;
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

            // Normalize payload shape for frontend: if bundles is a raw array, wrap as { data: [...] }
            $payload = $bundles;
            if (is_array($bundles) && (empty($bundles) || isset($bundles[0]) )) {
                $payload = ['data' => $bundles];
            }

            return response()->json([
                'success' => true,
                'data' => $payload,
                'message' => 'Data bundles retrieved successfully'
            ]);
        } catch (\Exception $e) {
            // Return static bundles on error to avoid frontend mock fallback
            $fallback = $this->getStaticDataBundles($network);
            $payload = ['data' => $fallback];
            return response()->json([
                'success' => true,
                'data' => $payload,
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
            ['code' => '500MB', 'name' => '500MB Daily', 'price' => 150],
            ['code' => '1GB', 'name' => '1GB Daily', 'price' => 300],
            ['code' => '2GB', 'name' => '2GB 2-Days', 'price' => 500],
            ['code' => '3GB', 'name' => '3GB Weekly', 'price' => 900],
            ['code' => '5GB', 'name' => '5GB Weekly', 'price' => 1500],
            ['code' => '10GB', 'name' => '10GB Monthly', 'price' => 3000],
        ];

        return array_map(function ($b) use ($networkKey) {
            return [
                'variation_id' => $networkKey . '_' . $b['code'],
                'data_plan' => $b['name'],
                'price' => $b['price'],
                'reseller_price' => $b['price'],
                'service_id' => $networkKey,
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

                return [
                    'id' => $t->id,
                    'type' => $type,
                    'amount' => (float) $t->amount,
                    'description' => (string) $t->description,
                    'status' => $status,
                    'reference' => $t->reference,
                    'created_at' => (string) $t->created_at,
                ];
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
            if ($user->balance < $request->amount) {
                return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
            }
            $reference = 'BET_' . Str::random(10);
            $serviceId = $this->normalizeBettingServiceId($request->service_id);
            $res = app(\App\Services\VtuNgService::class)->purchaseBetting($serviceId, $request->customer_id, (float)$request->amount, $reference);
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
        $res = app(\App\Services\VtuNgService::class)->verifyElectricityCustomer($request->service_id, $request->customer_id, $request->variation_id);
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
        $user = Auth::user();
        if ($user->balance < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
        }
        $reference = 'ELEC_' . Str::random(10);
        $res = app(\App\Services\VtuNgService::class)->purchaseElectricity($request->service_id, $request->customer_id, $request->variation_id, (float)$request->amount, $reference);
        if ($res['success']) {
            $user->updateBalance((float)$request->amount, 'subtract');
            // Record VTU order
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
            // Record transaction
            \DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => 'service_purchase',
                'amount' => (float)$request->amount,
                'balance_before' => $user->balance + (float)$request->amount,
                'balance_after' => $user->balance,
                'description' => 'Electricity bill: '.$request->service_id.' ('.$request->customer_id.')',
                'reference' => $reference,
                'status' => 'success',
                'metadata' => json_encode(['category' => 'electricity', 'service_id' => $request->service_id, 'customer_id' => $request->customer_id, 'variation_id' => $request->variation_id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'data' => [ 'reference' => $reference ], 'message' => 'Electricity purchased']);
        }
        return response()->json(['success' => false, 'message' => $res['message'] ?? 'Electricity purchase failed'], 400);
    }

    private function normalizeBettingServiceId(string $id): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($id));
    }
}
