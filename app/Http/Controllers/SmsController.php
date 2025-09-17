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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    private $smsProviderService;

    public function __construct(SmsProviderService $smsProviderService)
    {
        $this->smsProviderService = $smsProviderService;
    }

    /**
     * Convert provider-native price to NGN using configured FX and markup
     */
    private function convertPriceToNgn(float $baseCost, string $provider): float
    {
        // Defaults
        $fx = (float) (config('services.sms_fx.ngn_per_usd', 1600));
        $fxFloor = (float) (config('services.sms_fx.min_ngn_per_usd', 1200));
        if ($fx < $fxFloor) { $fx = $fxFloor; }
        $markupPct = (float) (config('services.sms_markup.percent', 0));

        // Provider-specific overrides (optional future use)
        $provFx = (float) (config("services.sms_fx.providers.{$provider}", 0));
        if ($provFx > 0) { $fx = max($provFx, $fxFloor); }
        $provMarkup = (float) (config("services.sms_markup.providers.{$provider}", -1));
        if ($provMarkup >= 0) { $markupPct = $provMarkup; }

        $ngn = $baseCost * $fx;
        if ($markupPct > 0) {
            $ngn = $ngn * (1 + ($markupPct / 100));
        }
        // Fixed VAT/add-on from settings table (sms_vat), default NGN 700
        try {
            $vat = (float) (DB::table('settings')->where('key', 'sms_vat')->value('value') ?? 700);
            if ($vat > 0) { $ngn += $vat; }
        } catch (\Throwable $e) {
            $ngn += 700; // fallback if settings table unavailable
        }
        // Round up to nearest 1 NGN to avoid fractional kobo noise
        return (float) ceil($ngn);
    }

    /**
     * Get available countries from SMS providers (optionally scoped to a provider)
     */
    public function getCountries(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms,textverified,smspool',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $provider = $request->get('provider');

            $countries = Cache::remember("sms:countries:" . ($provider ?: 'all'), 300, function () use ($provider) {
                $query = SmsService::active()->orderedByPriority();
                if ($provider) {
                    $query->byProvider($provider);
                }
                $smsServices = $query->get();
                $countries = [];
                foreach ($smsServices as $smsService) {
                    $providerCountries = $this->smsProviderService->getCountries($smsService);
                    foreach ($providerCountries as $country) {
                        $country['code'] = $country['code'] ?? ($country['iso'] ?? $country['id'] ?? null);
                        $country['name'] = $country['name'] ?? ($country['country'] ?? $country['title'] ?? '');
                        // Force provider attribution to current service to avoid mixed data
                        $country['provider'] = $smsService->provider;
                        if ($country['code'] && $country['name']) {
                            $countries[] = $country;
                        }
                    }
                }
                return $countries;
            });

            // Overlay DB-known country names for the provider
            if ($provider) {
                $map = DB::table('sms_countries')
                    ->where('provider', $provider)
                    ->pluck('name', 'country_id');
                $countries = collect($countries)->map(function ($c) use ($map) {
                    $cid = (string)$c['code'];
                    if (isset($map[$cid])) { $c['name'] = $map[$cid]; }
                    return $c;
                })->all();

                // Fallback: if no countries resolved from provider API, use curated list only
                if (empty($countries) && $map->isNotEmpty()) {
                    $countries = $map->map(function ($name, $cid) use ($provider) {
                        return ['code' => (string)$cid, 'name' => $name, 'provider' => $provider];
                    })->values()->all();
                }
            }

            // If provider specified, restrict to curated countries in DB
            if ($provider) {
                $curated = DB::table('sms_countries')
                    ->where('provider', $provider)
                    ->pluck('name', 'country_id');
                if ($curated->isNotEmpty()) {
                    $countries = collect($countries)->filter(function ($c) use ($curated) {
                        return $curated->has((string)$c['code']);
                    })->map(function ($c) use ($curated) {
                        $cid = (string)$c['code'];
                        if ($curated->has($cid)) { $c['name'] = $curated[$cid]; }
                        return $c;
                    })->values();
                }
            }

            // Remove duplicates by code and sort by curated weight then name
            $weightOrder = [
                '187','16','36','175','43','78','48','86','56','95','53','111','100','145','13','62','19','31','21','37','8','38','22','3','6','4','10','182','190','52','7','60','66','54','12','1001'
            ];
            $weightMap = [];
            foreach ($weightOrder as $idx => $id) { $weightMap[$id] = $idx; }

            $countries = collect($countries)
                ->unique('code')
                ->sort(function ($a, $b) use ($weightMap) {
                    $wa = $weightMap[$a['code']] ?? 9999;
                    $wb = $weightMap[$b['code']] ?? 9999;
                    if ($wa === $wb) {
                        return strcmp($a['name'], $b['name']);
                    }
                    return $wa <=> $wb;
                })
                ->values();

            // Hard filter by provider, if explicitly requested
            if ($provider) {
                $countries = collect($countries)
                    ->filter(function ($row) use ($provider) {
                        return is_array($row) && (($row['provider'] ?? '') === $provider);
                    })
                    ->values();
            }

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
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms,textverified,smspool'
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

            // Provider selection: if specified, strictly scope to that provider.

            $query = SmsService::active()->orderedByPriority();
            if ($provider) {
                $query->byProvider($provider);
            }

            $smsServices = $query->get();
            $services = [];

            foreach ($smsServices as $smsService) {
                $providerServices = $this->smsProviderService->getServices($smsService, $country);
                foreach ($providerServices as $service) {
                    // Force provider attribution to current service to avoid mixed data
                    $service['provider'] = $smsService->provider;
                    $service['provider_name'] = $smsService->name;
                    $services[] = $service;
                }
            }

            // Overlay service friendly names if available
            $friendlyRows = DB::table('sms_service_codes')
                ->whereIn('provider', $smsServices->pluck('provider')->unique())
                ->get(['code','name']);
            $friendlyNames = $friendlyRows->pluck('name', 'code');
            $svcWeights = [];
            foreach ($friendlyRows as $row) { $svcWeights[$row->code] = $svcWeights[$row->code] ?? count($svcWeights); }

            // Persist price cache per provider-country
            try {
                $priceCache = app(\App\Services\Sms\PriceCacheService::class);
                foreach ($smsServices as $svcModel) {
                    $prov = $svcModel->provider;
                    $provRows = array_values(array_filter($services, function ($r) use ($prov) {
                        return is_array($r) && ($r['provider'] ?? '') === $prov;
                    }));
                    if (!empty($provRows)) {
                        $priceCache->upsertPrices($prov, $country, $provRows);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Price cache upsert skipped', ['error' => $e->getMessage()]);
            }

            $services = collect($services)
                // Hard filter if provider explicitly requested
                ->when(!empty($provider), function ($c) use ($provider) {
                    return $c->filter(function ($row) use ($provider) {
                        return is_array($row) && (($row['provider'] ?? '') === $provider);
                    });
                })
                ->map(function ($s) use ($friendlyNames) {
                    $code = $s['service'] ?? null;
                    if ($code && isset($friendlyNames[$code])) {
                        $s['name'] = $friendlyNames[$code];
                    }
                    return $s;
                })
                // Convert prices to NGN for USD-based providers with markup
                ->map(function ($s) use ($provider) {
                    try {
                        $prov = $s['provider'] ?? $provider ?? null;
                        $currency = $s['currency'] ?? null;
                        // If provider handles conversion (e.g., dassy), do not convert here
                        if ($prov && strtolower((string)$prov) === 'dassy') {
                            return $s;
                        }
                        // Convert ONLY if backend did not already convert to NGN
                        if ($prov && isset($s['cost']) && ($currency === null || strtoupper((string)$currency) !== 'NGN')) {
                            $s['cost'] = $this->convertPriceToNgn((float)$s['cost'], (string)$prov);
                            $s['currency'] = 'NGN';
                        }
                    } catch (\Throwable $e) {
                        // Leave original cost on error
                    }
                    return $s;
                })
                // Remove duplicates and sort by cost
                ->unique('service')
                ->sort(function ($a, $b) use ($svcWeights) {
                    $wa = $svcWeights[$a['service']] ?? 9999;
                    $wb = $svcWeights[$b['service']] ?? 9999;
                    if ($wa === $wb) {
                        return ($a['cost'] <=> $b['cost']);
                    }
                    return $wa <=> $wb;
                })
                ->values();

            // Fallback: if no services returned, surface curated service codes with placeholder pricing
            if ($services->isEmpty()) {
                $curated = DB::table('sms_service_codes')
                    ->whereIn('provider', $smsServices->pluck('provider')->unique())
                    ->get(['code','name']);
                if ($curated->isNotEmpty()) {
                    $services = $curated->map(function ($row) {
                        return [
                            'service' => $row->code,
                            'name' => $row->name,
                            'cost' => 0,
                            'count' => 0,
                        ];
                    })->values();
                }
            }

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
        Log::info("=== SMS ORDER ENDPOINT CALLED ===", [
            'timestamp' => now(),
            'request_data' => $request->all(),
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip()
        ]);
        
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:10',
            'service' => 'required|string',
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms,textverified,smspool',
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
            $country = strtoupper($request->country);
            $service = (string) $request->service;
            $provider = $request->provider;
            $mode = $request->mode ?? 'auto'; // Default to auto mode

            // Check user balance
            if ($user->balance < 150) { // Minimum cost for SMS service
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Please recharge your account.'
                ], 400);
            }

            // Provider-specific payload validation to avoid mixed/invalid combos
            if ($provider === 'textverified') {
                if ($country !== 'US') {
                    return response()->json(['success' => false, 'message' => 'TextVerified requires US.'], 422);
                }
                if (preg_match('/^\d+$/', $service)) {
                    return response()->json(['success' => false, 'message' => 'TextVerified requires serviceName (string), not numeric code.'], 422);
                }
            }
            if (in_array($provider, ['tiger_sms', '5sim', 'dassy'])) {
                if (preg_match('/^\d+$/', $service)) {
                    return response()->json(['success' => false, 'message' => 'Selected provider requires a string service code (e.g., whatsapp).'], 422);
                }
            }
            if ($provider === 'smspool') {
                // smspool accepts numeric service codes; no additional guard
            }

            // Get available SMS services based on mode
            $query = SmsService::active();
            
            Log::info("SMS Order Request", [
                'user_id' => $user->id,
                'country' => $country,
                'service' => $service,
                'provider' => $provider,
                'mode' => $mode
            ]);
            
            if ($mode === 'manual') {
                // Manual mode: Use specific provider if provided, otherwise show error
                if (!$provider) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Provider is required for manual mode. Please select a specific SMS provider.'
                    ], 400);
                }
                $query->byProvider($provider);
                Log::info("Manual mode: Filtering by provider", ['provider' => $provider]);
            } else {
                // Auto mode: Get all active services, ordered by success rate and priority
                $query->orderedByPriority();
                Log::info("Auto mode: Getting all providers by priority");
            }

            $smsServices = $query->get();
            
            Log::info("Available SMS Services", [
                'count' => $smsServices->count(),
                'services' => $smsServices->map(function($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'provider' => $service->provider,
                        'priority' => $service->priority,
                        'is_active' => $service->is_active
                    ];
                })->toArray()
            ]);

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
                    Log::info("Attempting to create order with provider", [
                        'provider' => $smsService->provider,
                        'provider_name' => $smsService->name,
                        'priority' => $smsService->priority,
                        'mode' => $mode
                    ]);
                    
                    $orderData = $this->smsProviderService->createOrder($smsService, $country, $service);

                    // Determine charge in NGN if provider didn't supply it
                    $charge = (float)($orderData['cost'] ?? 0);
                    if ($charge <= 0) {
                        try {
                            $svcRows = $this->smsProviderService->getServices($smsService, $country);
                            foreach ($svcRows as $row) {
                                if (isset($row['service']) && (string)$row['service'] === (string)$service) {
                                    $rowCost = (float)($row['cost'] ?? 0);
                                    $rowCurrency = strtoupper((string)($row['currency'] ?? ''));
                                    if ($rowCost > 0) {
                                        if ($rowCurrency === 'NGN') {
                                            $charge = $rowCost;
                                        } else {
                                            $charge = $this->convertPriceToNgn($rowCost, (string)$smsService->provider);
                                        }
                                    }
                                    break;
                                }
                            }
                        } catch (\Throwable $e) {
                            // Leave $charge as 0 on failure; will be handled below
                        }
                    }
                    if ($charge <= 0) {
                        throw new \RuntimeException('Could not determine SMS price for charge');
                    }
                    $orderData['cost'] = (float) ceil($charge);
                    
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
     * Countries filtered by service (service-first flow)
     * GET /api/sms/countries-by-service?service=wa&provider=tiger_sms
     */
    public function getCountriesByService(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string',
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms,textverified,smspool'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = strtolower($request->get('service'));
            $provider = $request->get('provider');

            $query = SmsService::active();
            if ($provider) {
                $query->byProvider($provider);
            } else {
                $query->orderedByPriority();
            }

            $smsServices = $query->get();
            $cacheKey = 'sms:countries_by_service:' . ($provider ?: 'all') . ':' . $service;
            $results = Cache::remember($cacheKey, 300, function () use ($smsServices, $service) {
                $acc = [];
                foreach ($smsServices as $smsService) {
                    $rows = $this->smsProviderService->getCountriesByService($smsService, $service);
                    foreach ($rows as $row) {
                        // Force provider attribution to current service to avoid mixed data
                        $row['provider'] = $smsService->provider;
                        $acc[] = $row;
                    }
                }
                return $acc;
            });

            // Deduplicate by country_id+provider and sort by cost asc then count desc
            $results = collect($results)
                // Hard filter if provider explicitly requested
                ->when(!empty($provider), function ($c) use ($provider) {
                    return $c->filter(function ($row) use ($provider) {
                        return is_array($row) && (($row['provider'] ?? '') === $provider);
                    });
                })
                ->unique(function ($r) { return ($r['provider'] ?? '') . '|' . ($r['country_id'] ?? ''); })
                ->sort(function ($a, $b) {
                    $cmp = ($a['cost'] <=> $b['cost']);
                    return $cmp !== 0 ? $cmp : ($b['count'] <=> $a['count']);
                })
                ->values();

            // Fallback: curated countries if provider returned empty
            if ($results->isEmpty()) {
                $provList = $provider ? [$provider] : $smsServices->pluck('provider')->unique()->values()->all();
                $curated = collect();
                foreach ($provList as $prov) {
                    $rows = DB::table('sms_countries')->where('provider', $prov)->get(['country_id','name']);
                    foreach ($rows as $r) {
                        $curated->push([
                            'provider' => $prov,
                            'country_id' => (string)$r->country_id,
                            'country_name' => $r->name,
                            'cost' => 0,
                            'count' => 0,
                        ]);
                    }
                }
                $results = $curated->unique(function ($r) { return ($r['provider'] ?? '') . '|' . ($r['country_id'] ?? ''); })->values();
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Countries by service retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve countries by service: ' . $e->getMessage()
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
                ->select('id', 'name', 'provider', 'success_rate', 'total_orders', 'successful_orders', 'last_balance_check')
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
                        // Do not expose balance to clients
                        'last_balance_check' => $provider->last_balance_check,
                        'status' => 'available',
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
     * Get server list for frontend display
     * This endpoint provides the server list that the frontend expects
     */
    public function getServers(): JsonResponse
    {
        try {
            $servers = SmsService::active()
                ->select('id', 'name', 'provider', 'success_rate', 'total_orders', 'successful_orders', 'priority', 'created_at')
                ->orderBy('priority', 'asc')
                ->orderBy('success_rate', 'desc')
                ->get()
                ->map(function ($server) {
                    return [
                        'id' => $server->id,
                        'name' => $server->name,
                        'display_name' => $server->name, // Use the name column as display name
                        'provider' => $server->provider,
                        'success_rate' => $server->success_rate,
                        'total_orders' => $server->total_orders,
                        'successful_orders' => $server->successful_orders,
                        'status' => 'active',
                        'priority' => $server->priority,
                        'location' => $this->getServerLocation($server->provider),
                        'region' => $this->getServerRegion($server->provider),
                        'created_at' => $server->created_at
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $servers,
                'message' => 'Servers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve servers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get server location based on provider
     */
    private function getServerLocation(string $provider): string
    {
        $locations = [
            '5sim' => 'Global',
            'tiger_sms' => 'Global',
            'dassy' => 'Global',
            'textverified' => 'United States',
            'smspool' => 'Global'
        ];

        return $locations[$provider] ?? 'Global';
    }

    /**
     * Get server region based on provider
     */
    private function getServerRegion(string $provider): string
    {
        $regions = [
            '5sim' => 'Global',
            'tiger_sms' => 'Global',
            'dassy' => 'Global',
            'textverified' => 'North America',
            'smspool' => 'Global'
        ];

        return $regions[$provider] ?? 'Global';
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
