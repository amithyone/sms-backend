<?php

namespace App\Http\Controllers;

use App\Services\IrechargeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VtuController extends Controller
{
    private $irechargeService;

    public function __construct(IrechargeService $irechargeService)
    {
        $this->irechargeService = $irechargeService;
    }

    /**
     * Get available airtime networks
     */
    public function getAirtimeNetworks(): JsonResponse
    {
        try {
            $networks = $this->irechargeService->getAirtimeNetworks();

            return response()->json([
                'success' => true,
                'data' => $networks,
                'message' => 'Airtime networks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve airtime networks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available data networks
     */
    public function getDataNetworks(): JsonResponse
    {
        try {
            $networks = $this->irechargeService->getDataNetworks();

            return response()->json([
                'success' => true,
                'data' => $networks,
                'message' => 'Data networks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data networks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data bundles for a specific network
     */
    public function getDataBundles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            $bundles = $this->irechargeService->getDataBundles($request->network);

            return response()->json([
                'success' => true,
                'data' => $bundles,
                'message' => 'Data bundles retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data bundles: ' . $e->getMessage()
            ], 500);
        }
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
            if (!$this->irechargeService->validatePhoneNumber($phone, $network)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number for the selected network.'
                ], 400);
            }

            // Generate reference
            $reference = 'VTU_' . Str::random(10);

            // Purchase airtime
            $result = $this->irechargeService->purchaseAirtime($network, $phone, $amount, $reference);

            if ($result['success']) {
                // Deduct balance from user
                $user->updateBalance($amount, 'subtract');

                // Create transaction record
                $user->transactions()->create([
                    'type' => 'airtime_purchase',
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
                        'provider' => 'irecharge',
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
            if (!$this->irechargeService->validatePhoneNumber($phone, $network)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number for the selected network.'
                ], 400);
            }

            // Generate reference
            $reference = 'VTU_' . Str::random(10);

            // Purchase data bundle
            $result = $this->irechargeService->purchaseDataBundle($network, $phone, $plan, $reference);

            if ($result['success']) {
                // Deduct balance from user
                $user->updateBalance($amount, 'subtract');

                // Create transaction record
                $user->transactions()->create([
                    'type' => 'data_purchase',
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
                        'provider' => 'irecharge',
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
            return response()->json([
                'success' => false,
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
            $result = $this->irechargeService->getTransactionStatus($request->reference);

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
            $result = $this->irechargeService->getBalance();

            return response()->json([
                'success' => $result['success'],
                'data' => [
                    'balance' => $result['balance']
                ],
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get provider balance: ' . $e->getMessage()
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
            $isValid = $this->irechargeService->validatePhoneNumber($request->phone, $request->network);

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
}
