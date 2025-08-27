<?php

namespace App\Http\Controllers;

use App\Models\SmsService;
use App\Models\SmsOrder;
use App\Models\User;
use App\Services\SmsProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    private $smsProviderService;

    public function __construct(SmsProviderService $smsProviderService)
    {
        $this->smsProviderService = $smsProviderService;
    }

    /**
     * Get available countries from all SMS providers
     */
    public function getCountries(): JsonResponse
    {
        try {
            $smsServices = SmsService::active()->orderedByPriority()->get();
            $countries = [];

            foreach ($smsServices as $smsService) {
                $providerCountries = $this->smsProviderService->getCountries($smsService);
                foreach ($providerCountries as $country) {
                    $country['provider'] = $smsService->provider;
                    $countries[] = $country;
                }
            }

            // Remove duplicates and sort by name
            $countries = collect($countries)->unique('code')->sortBy('name')->values();

            return response()->json([
                'success' => true,
                'data' => $countries,
                'message' => 'Countries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available services for a specific country
     */
    public function getServices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:10',
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $country = $request->country;
            $provider = $request->provider;

            $query = SmsService::active()->orderedByPriority();
            if ($provider) {
                $query->byProvider($provider);
            }

            $smsServices = $query->get();
            $services = [];

            foreach ($smsServices as $smsService) {
                $providerServices = $this->smsProviderService->getServices($smsService, $country);
                foreach ($providerServices as $service) {
                    $service['provider'] = $smsService->provider;
                    $service['provider_name'] = $smsService->name;
                    $services[] = $service;
                }
            }

            // Remove duplicates and sort by cost
            $services = collect($services)->unique('service')->sortBy('cost')->values();

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Services retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new SMS order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:10',
            'service' => 'required|string',
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms',
            'mode' => 'nullable|string|in:auto,manual'
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
            $country = $request->country;
            $service = $request->service;
            $provider = $request->provider;
            $mode = $request->mode ?? 'auto'; // Default to auto mode

            // Check user balance
            if ($user->balance < 150) { // Minimum cost for SMS service
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Please recharge your account.'
                ], 400);
            }

            // Get available SMS services based on mode
            $query = SmsService::active();
            
            if ($mode === 'manual') {
                // Manual mode: Use specific provider if provided, otherwise show error
                if (!$provider) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Provider is required for manual mode. Please select a specific SMS provider.'
                    ], 400);
                }
                $query->byProvider($provider);
            } else {
                // Auto mode: Get all active services, ordered by success rate and priority
                $query->orderedByPriority();
            }

            $smsServices = $query->get();

            if ($smsServices->isEmpty()) {
                $errorMessage = $mode === 'manual' 
                    ? "No SMS services available for provider: {$provider}"
                    : 'No SMS services available';
                    
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            // For auto mode, shuffle services to randomize selection while respecting priority
            if ($mode === 'auto') {
                $smsServices = $smsServices->shuffle()->sortBy('priority');
            }

            // Try to create order with each service until successful
            foreach ($smsServices as $smsService) {
                try {
                    $orderData = $this->smsProviderService->createOrder($smsService, $country, $service);
                    
                    // Create order in database
                    $order = SmsOrder::create([
                        'user_id' => $user->id,
                        'sms_service_id' => $smsService->id,
                        'order_id' => 'SMS_' . Str::random(10),
                        'phone_number' => $orderData['phone_number'],
                        'country' => $country,
                        'service' => $service,
                        'cost' => $orderData['cost'],
                        'status' => $orderData['status'],
                        'expires_at' => $orderData['expires_at'],
                        'provider_order_id' => $orderData['order_id'],
                        'metadata' => [
                            'provider' => $smsService->provider,
                            'provider_name' => $smsService->name,
                            'mode' => $mode,
                            'success_rate' => $smsService->success_rate
                        ]
                    ]);

                    // Deduct balance from user
                    $user->updateBalance($orderData['cost'], 'subtract');

                    // Create transaction record
                    $user->transactions()->create([
                        'type' => 'service_purchase',
                        'amount' => $orderData['cost'],
                        'balance_before' => $user->balance + $orderData['cost'],
                        'balance_after' => $user->balance,
                        'description' => "SMS verification for {$service} ({$country}) via {$smsService->name}",
                        'reference' => 'SMS_' . Str::random(15),
                        'status' => 'success',
                        'metadata' => [
                            'order_id' => $order->order_id,
                            'phone_number' => $orderData['phone_number'],
                            'service' => $service,
                            'provider' => $smsService->provider,
                            'mode' => $mode
                        ]
                    ]);

                    // Update SMS service stats
                    $smsService->incrementOrders(true);

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'order_id' => $order->order_id,
                            'phone_number' => $order->getFormattedPhoneNumber(),
                            'service' => $order->getServiceDisplayName(),
                            'country' => $country,
                            'cost' => $order->cost,
                            'status' => $order->status,
                            'expires_at' => $order->expires_at,
                            'provider' => $smsService->provider,
                            'provider_name' => $smsService->name,
                            'mode' => $mode,
                            'success_rate' => $smsService->success_rate
                        ],
                        'message' => $mode === 'auto' 
                            ? "SMS order created successfully with {$smsService->name} (Auto-selected)"
                            : "SMS order created successfully with {$smsService->name}"
                    ]);

                } catch (\Exception $e) {
                    // Log error and continue to next service
                    \Log::error("Failed to create order with {$smsService->provider}: " . $e->getMessage());
                    continue;
                }
            }

            $errorMessage = $mode === 'manual' 
                ? "Failed to create SMS order with provider {$provider}. Please try again later."
                : 'Failed to create SMS order. All providers are currently unavailable.';

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SMS order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS code for an order
     */
    public function getSmsCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string'
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
            $orderId = $request->order_id;

            $order = SmsOrder::where('order_id', $orderId)
                ->where('user_id', $user->id)
                ->with('smsService')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            if ($order->isCompleted()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'sms_code' => $order->sms_code,
                        'status' => $order->status,
                        'received_at' => $order->received_at
                    ],
                    'message' => 'SMS code retrieved successfully'
                ]);
            }

            if ($order->isExpired()) {
                $order->markAsExpired();
                return response()->json([
                    'success' => false,
                    'message' => 'Order has expired'
                ], 400);
            }

            // Get SMS code from provider
            $smsCode = $this->smsProviderService->getSmsCode($order->smsService, $order->provider_order_id);

            if ($smsCode) {
                $order->markAsCompleted($smsCode);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'sms_code' => $smsCode,
                        'status' => $order->status,
                        'received_at' => $order->received_at
                    ],
                    'message' => 'SMS code received successfully'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sms_code' => null,
                    'status' => $order->status,
                    'message' => 'SMS code not yet received'
                ],
                'message' => 'Waiting for SMS code'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SMS code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an SMS order
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string'
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
            $orderId = $request->order_id;

            $order = SmsOrder::where('order_id', $orderId)
                ->where('user_id', $user->id)
                ->with('smsService')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            if ($order->isCompleted() || $order->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled'
                ], 400);
            }

            // Cancel order with provider
            $cancelled = $this->smsProviderService->cancelOrder($order->smsService, $order->provider_order_id);

            if ($cancelled) {
                $order->markAsCancelled();

                // Refund user balance
                $user->updateBalance($order->cost, 'add');

                // Create refund transaction
                $user->transactions()->create([
                    'type' => 'refund',
                    'amount' => $order->cost,
                    'balance_before' => $user->balance - $order->cost,
                    'balance_after' => $user->balance,
                    'description' => "Refund for cancelled SMS order {$order->order_id}",
                    'reference' => 'REF_' . Str::random(15),
                    'status' => 'success',
                    'metadata' => [
                        'order_id' => $order->order_id,
                        'original_transaction' => 'SMS order cancellation'
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully. Balance has been refunded.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's SMS orders
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $status = $request->get('status');
            $limit = $request->get('limit', 20);

            $query = SmsOrder::where('user_id', $user->id)
                ->with('smsService')
                ->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->limit($limit)->get();

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'phone_number' => $order->getFormattedPhoneNumber(),
                    'service' => $order->getServiceDisplayName(),
                    'country' => $order->country,
                    'cost' => $order->cost,
                    'status' => $order->status,
                    'status_label' => $order->getStatusLabel(),
                    'sms_code' => $order->sms_code,
                    'expires_at' => $order->expires_at,
                    'received_at' => $order->received_at,
                    'provider' => $order->smsService->provider,
                    'created_at' => $order->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'message' => 'Orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available providers with success rates for manual selection
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = SmsService::active()
                ->select('id', 'name', 'provider', 'success_rate', 'total_orders', 'successful_orders', 'balance', 'last_balance_check')
                ->orderBy('success_rate', 'desc')
                ->orderBy('priority', 'asc')
                ->get()
                ->map(function ($provider) {
                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                        'provider' => $provider->provider,
                        'success_rate' => $provider->success_rate,
                        'total_orders' => $provider->total_orders,
                        'successful_orders' => $provider->successful_orders,
                        'balance' => $provider->balance,
                        'last_balance_check' => $provider->last_balance_check,
                        'status' => $provider->balance > 0 ? 'available' : 'low_balance',
                        'display_name' => $provider->name . ' (' . number_format($provider->success_rate, 1) . '% success)'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $providers,
                'message' => 'Providers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve providers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS service statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = Auth::user();

            $stats = [
                'total_orders' => SmsOrder::where('user_id', $user->id)->count(),
                'completed_orders' => SmsOrder::where('user_id', $user->id)->completed()->count(),
                'pending_orders' => SmsOrder::where('user_id', $user->id)->pending()->count(),
                'total_spent' => SmsOrder::where('user_id', $user->id)->sum('cost'),
                'recent_orders' => SmsOrder::where('user_id', $user->id)
                    ->with('smsService')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'order_id' => $order->order_id,
                            'service' => $order->getServiceDisplayName(),
                            'status' => $order->status,
                            'created_at' => $order->created_at
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
