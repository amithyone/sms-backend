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
     * Log SMS operation with detailed context
     */
    private function logSmsOperation(string $operation, array $context = [], string $level = 'info'): void
    {
        $user = Auth::user();
        $requestId = request()->header('X-Request-ID', Str::uuid()->toString());
        
        $logData = array_merge([
            'operation' => $operation,
            'request_id' => $requestId,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('sms')->$level("SMS {$operation}", $logData);
    }

    /**
     * Log SMS error with detailed context
     */
    private function logSmsError(string $operation, \Exception $exception, array $context = []): void
    {
        $user = Auth::user();
        $requestId = request()->header('X-Request-ID', Str::uuid()->toString());
        
        $logData = array_merge([
            'operation' => $operation,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'error_trace' => $exception->getTraceAsString(),
            'request_id' => $requestId,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('sms')->error("SMS {$operation} Error", $logData);
        Log::channel('errors')->error("SMS {$operation} Error", $logData);
    }

    /**
     * Convert provider-native price to NGN using configured FX and markup
     * Enforces minimum price of ₦1500 for all SMS services
     */
    private function convertPriceToNgn(float $baseCost, string $provider, string $sourceCurrency = 'USD'): float
    {
        // Defaults (treat baseCost in USD unless sourceCurrency says otherwise)
        $fx = (float) (config('services.sms_fx.ngn_per_usd', 1600));
        $fxFloor = (float) (config('services.sms_fx.min_ngn_per_usd', 1200));
        if ($fx < $fxFloor) { $fx = $fxFloor; }
        $markupPct = (float) (config('services.sms_markup.percent', 10));

        // Provider-specific overrides (optional future use)
        $provFx = (float) (config("services.sms_fx.providers.{$provider}", 0));
        if ($provFx > 0) { $fx = max($provFx, $fxFloor); }
        $provMarkup = (float) (config("services.sms_markup.providers.{$provider}", -1));
        if ($provMarkup >= 0) { $markupPct = $provMarkup; }

        // Normalize source to USD
        $usd = $baseCost;
        $cur = strtoupper((string)$sourceCurrency);
        if ($cur === 'RUB') {
            // Convert RUB -> USD using usd_per_rub
            $usdPerRub = (float) (config('services.sms_fx.usd_per_rub', 0.011));
            $usd = $baseCost * $usdPerRub;
        } elseif ($cur === 'NGN') {
            // Already NGN; skip conversion and return clamps/markup only
            $usd = $baseCost / max($fx, 1.0);
        }

        // Convert USD to NGN
        $ngn = $usd * $fx;
        
        // Apply markup percentage
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
        $ngn = (float) ceil($ngn);
        
        // IMPORTANT: Enforce minimum price for all SMS services
        // Get minimum price from settings table (default ₦1500)
        try {
            $minPrice = (float) (DB::table('settings')->where('key', 'sms_min_price')->value('value') ?? 1500);
        } catch (\Throwable $e) {
            $minPrice = 1500.0; // fallback if settings table unavailable
        }
        
        if ($ngn < $minPrice) {
            $ngn = $minPrice;
        }
        
        // NEW: Apply profit margin percentage ON TOP of the final price
        try {
            $profitMargin = (float) (DB::table('settings')->where('key', 'sms_profit_margin')->value('value') ?? 15);
            if ($profitMargin > 0) {
                $ngn = $ngn * (1 + ($profitMargin / 100));
                $ngn = (float) ceil($ngn); // Round up again after profit margin
            }
        } catch (\Throwable $e) {
            // If can't get profit margin, apply default 15%
            $ngn = $ngn * 1.15;
            $ngn = (float) ceil($ngn);
        }
        
        return $ngn;
    }

    /**
     * Check if a service is popular and should be prioritized
     */
    private function isPopularService(string $serviceName): bool
    {
        $popularServices = [
            // TOP PRIORITY - Most requested services (All providers)
            'whatsapp', 'wa', 'telegram', 'tg', 'signal', 'bw',
            'tinder', 'oi', 'facebook', 'fb', 'google', 'go', 'gmail',
            'payoneer', 'tiktok', 'lf', 'linkedin', 'tn',
            
            // HIGH PRIORITY - Dating and social platforms
            'bumble', 'mo', 'discord', 'ds', 'instagram', 'ig',
            'snapchat', 'snap', 'twitter', 'x.com', 'tw',
            
            // MEDIUM PRIORITY - Business and entertainment
            'amazon', 'am', 'uber', 'ub', 'paypal', 'netflix', 'spotify', 'youtube',
            'googlechat', 'google_voice', 'gf', 'verizon', 'vz',
            
            // ADDITIONAL POPULAR SERVICES (All providers)
            'aarp', 'albertsons', 'biltrewards', 'bright', 'craigslist', 'wc',
            'docusign', 'evgo', 'fetchrewards', 'frisbee', 'go2bank', 'gofundme',
            'golden1', 'ajn', 'greenlight', 'innago', 'lego', 'lightningai',
            'lightstream', 'noonlight', 'vm', 'dr', 'ts', 'pelago', 'playful',
            'pogo', 'r4r', 'schwab', 'seatgeek', 'fu', 'swag', 'ticketswap',
            'timewall', 'ufb', 'upward', 'verasight', 'veriswap', 'wallethub',
            'wr', 'walmartmoneycard', 'wayfair', 'waymo', 'wfargo', 'ago'
        ];

        foreach ($popularServices as $popular) {
            if (strpos($serviceName, $popular) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate priority score for service sorting (higher = more important)
     */
    private function calculateServicePriority(string $serviceName, array $service): int
    {
        $priority = 0;
        
        // Popular services get highest priority
        if ($this->isPopularService($serviceName)) {
            $priority += 1000;
            
            // TOP PRIORITY SERVICES (highest demand)
            if (strpos($serviceName, 'whatsapp') !== false || strpos($serviceName, 'wa') !== false) {
                $priority += 1000; // Highest priority
            }
            
            if (strpos($serviceName, 'telegram') !== false || strpos($serviceName, 'tg') !== false) {
                $priority += 900; // Second highest
            }
            
            if (strpos($serviceName, 'signal') !== false || strpos($serviceName, 'bw') !== false) {
                $priority += 850; // Third highest
            }
            
            if (strpos($serviceName, 'tinder') !== false || strpos($serviceName, 'oi') !== false) {
                $priority += 800; // Dating apps high priority
            }
            
            // HIGH PRIORITY SERVICES
            if (strpos($serviceName, 'facebook') !== false || strpos($serviceName, 'fb') !== false) {
                $priority += 700;
            }
            
            if (strpos($serviceName, 'google') !== false || strpos($serviceName, 'go') !== false || strpos($serviceName, 'gmail') !== false) {
                $priority += 650;
            }
            
            if (strpos($serviceName, 'payoneer') !== false) {
                $priority += 600;
            }
            
            if (strpos($serviceName, 'tiktok') !== false || strpos($serviceName, 'lf') !== false) {
                $priority += 550;
            }
            
            if (strpos($serviceName, 'linkedin') !== false || strpos($serviceName, 'tn') !== false) {
                $priority += 500;
            }
            
            // MEDIUM PRIORITY SERVICES
            if (strpos($serviceName, 'bumble') !== false || strpos($serviceName, 'mo') !== false) {
                $priority += 450;
            }
            
            if (strpos($serviceName, 'discord') !== false || strpos($serviceName, 'ds') !== false) {
                $priority += 400;
            }
            
            if (strpos($serviceName, 'instagram') !== false || strpos($serviceName, 'ig') !== false) {
                $priority += 350;
            }
            
            if (strpos($serviceName, 'amazon') !== false || strpos($serviceName, 'am') !== false) {
                $priority += 300;
            }
            
            if (strpos($serviceName, 'uber') !== false || strpos($serviceName, 'ub') !== false) {
                $priority += 250;
            }
        }
        
        // Consider success rate (if available)
        if (isset($service['success_rate']) && is_numeric($service['success_rate'])) {
            $priority += (int)$service['success_rate'];
        }
        
        // Consider provider reliability
        $provider = $service['provider'] ?? '';
        switch (strtolower($provider)) {
            case '5sim':
                $priority += 200; // High reliability
                break;
            case 'smspool':
                $priority += 150;
                break;
            case 'dassy':
                $priority += 100;
                break;
            case 'tigersms':
                $priority += 50;
                break;
        }
        
        // Prefer lower cost (better value)
        if (isset($service['cost']) && is_numeric($service['cost'])) {
            $cost = (float)$service['cost'];
            if ($cost > 0) {
                $priority += max(0, 100 - (int)($cost / 100)); // Lower cost = higher priority
            }
        }
        
        return $priority;
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

            // 1) Try cache-first for provider-scoped countries
            if ($provider) {
                $cachedCountries = DB::table('sms_country_catalog')
                    ->where('provider', $provider)
                    ->get(['country_code','country_name']);

                if ($cachedCountries->isNotEmpty()) {
                    $countries = $cachedCountries->map(function ($row) use ($provider) {
                        return [
                            'code' => (string)$row->country_code,
                            'name' => (string)$row->country_name,
                            'provider' => $provider,
                        ];
                    })->values()->all();

                    return response()->json([
                        'success' => true,
                        'data' => $countries,
                        'message' => 'Countries retrieved from cache'
                    ]);
                }
            }

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
            // EXCEPTION: For smspool, dassy, tiger_sms, 5sim -> show all provider countries
            if ($provider && !in_array(strtolower($provider), ['smspool','dassy','tiger_sms','5sim'])) {
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

            // 2) Upsert into country catalog for future cache hits (provider-scoped)
            if ($provider) {
                try {
                    foreach ($countries as $c) {
                        DB::table('sms_country_catalog')->updateOrInsert(
                            [ 'provider' => $provider, 'country_code' => (string)$c['code'] ],
                            [ 'country_name' => (string)$c['name'], 'updated_at' => now(), 'created_at' => now() ]
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed upserting sms_country_catalog', ['error' => $e->getMessage(), 'provider' => $provider]);
                }
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
            $countryKey = strtoupper((string)$country);
            $cachedServicesCollection = collect();
            
            // PERFORMANCE: Cache key for this request
            $cacheKey = "sms_services:{$country}:" . ($provider ?? 'all') . ":" . now()->format('Y-m-d-H-i');
            $cacheDuration = $provider === 'dassy' ? 5 : 15; // Cache DASSY for 5 minutes, others for 15 minutes
            
            // Try to get from cache first (for expensive providers)
            // Temporarily disabled caching for DASSY to fix country filtering issue
            if ($provider === 'dassy') {
                // $cached = Cache::get($cacheKey);
                // if ($cached) {
                //     return response()->json([
                //         'success' => true,
                //         'data' => $cached,
                //         'cached' => true
                //     ]);
                // }
            }

            // Provider selection: if specified, strictly scope to that provider.

            // Simple cache for slow providers (e.g., smspool)
            if ($provider && strtolower($provider) !== 'dassy') {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return response()->json([
                        'success' => true,
                        'data' => $cached,
                        'cached' => true,
                        'message' => 'Services retrieved (cached)'
                    ]);
                }
            }

            // 1) Try DB price catalog first for provider+country (cache-first path)
            if ($provider) {
                $priceRows = DB::table('sms_service_country_prices')
                    ->where('provider', $provider)
                    ->where('country_code', $countryKey)
                    ->get(['service','cost','count','last_seen_at','provider_currency']);

                if ($priceRows->isNotEmpty()) {
                    // Friendly names map
                    $friendlyRows = DB::table('sms_service_codes')
                        ->where(function($q) use ($provider) {
                            $q->where('provider', $provider)->orWhere('provider', 'all');
                        })
                        ->get(['code','name']);
                    $friendlyNames = $friendlyRows->pluck('name','code');

                    // If provider_currency exists in table, fetch it too
                    $services = $priceRows->map(function ($row) use ($friendlyNames, $provider) {
                        $code = (string)$row->service;
                        $name = (string)($friendlyNames[$code] ?? ucfirst($code));
                        $rowCost = (float)$row->cost;
                        $currencyCol = property_exists($row, 'provider_currency') ? (string)($row->provider_currency ?? '') : '';
                        $currency = strtoupper($currencyCol);
                        // Only convert if currency is USD (or unknown treated as USD); if NGN, use as-is
                        if ($currency === 'NGN') {
                            $ngn = $rowCost;
                        } else {
                            $src = in_array($currency, ['USD','RUB']) ? $currency : 'USD';
                            $ngn = $this->convertPriceToNgn($rowCost, (string)$provider, $src);
                        }
                        // Clamp
                        $limits = config('services.sms_price_limits');
                        $maxNgn = (float)($limits['max_ngn'] ?? 3000000);
                        $minNgn = (float)($limits['min_ngn'] ?? 1500);
                        if ($ngn > $maxNgn) { $ngn = $maxNgn; }
                        if ($ngn < $minNgn) { $ngn = $minNgn; }
                        return [
                            'service' => $code,
                            'name' => $name,
                            'cost' => $ngn,
                            'count' => (int)$row->count,
                            'provider' => $provider,
                            'provider_name' => DB::table('sms_services')->where('provider',$provider)->value('name') ?? $provider,
                        ];
                    })->values();

                    // For most providers, serve catalog immediately; for textverified, keep to merge with live to ensure full list
                    if (strtolower($provider) !== 'textverified') {
                        // Cache response for a short period
                        if (strtolower($provider) !== 'dassy') {
                            Cache::put($cacheKey, $services, now()->addMinutes(10));
                        }
                        return response()->json([
                            'success' => true,
                            'data' => $services,
                            'message' => 'Services retrieved from catalog'
                        ]);
                    } else {
                        $cachedServicesCollection = collect($services);
                    }
                }
            }

            // Build provider query; for providers that don't track balance in DB (dassy, 5sim, textverified),
            // avoid the active() scope if it enforces balance > 0
            $balanceAgnostic = $provider && in_array(strtolower($provider), ['dassy','5sim','textverified']);
            if ($balanceAgnostic) {
                $query = SmsService::query()->where('provider', $provider)->where('is_active', 1)->orderBy('priority');
            } else {
                $query = SmsService::active()->orderedByPriority();
                if ($provider) {
                    $query->byProvider($provider);
                }
            }

            $smsServices = $query->get();
            $services = [];

            foreach ($smsServices as $smsService) {
                Log::info('SmsController calling getServices', [
                    'provider' => $smsService->provider,
                    'country' => $country,
                    'service_id' => $smsService->id
                ]);
                $providerServices = $this->smsProviderService->getServices($smsService, $country);
                Log::info('SmsController getServices result', [
                    'provider' => $smsService->provider,
                    'country' => $country,
                    'services_count' => count($providerServices)
                ]);
                foreach ($providerServices as $service) {
                    // Force provider attribution to current service to avoid mixed data
                    $service['provider'] = $smsService->provider;
                    $service['provider_name'] = $smsService->name;
                    $services[] = $service;
                }
            }

            // Overlay service friendly names if available
            $friendlyRows = DB::table('sms_service_codes')
                ->where(function($query) use ($smsServices) {
                    $providers = $smsServices->pluck('provider')->unique();
                    $query->whereIn('provider', $providers)
                          ->orWhere('provider', 'all');
                })
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

            // If we have cached TextVerified services, merge them before normalization to ensure full list
            if (!empty($provider) && strtolower($provider) === 'textverified' && $cachedServicesCollection->isNotEmpty()) {
                $services = array_merge($services, $cachedServicesCollection->all());
            }

            $services = collect($services)
                // Hard filter if provider explicitly requested
                ->when(!empty($provider), function ($c) use ($provider) {
                    return $c->filter(function ($row) use ($provider) {
                        return is_array($row) && (($row['provider'] ?? '') === $provider);
                    });
                })
                // PERFORMANCE: Limit services for slow providers to improve response time
                ->when($provider === 'dassy', function ($c) {
                    // Ensure top services (WhatsApp, Telegram) are always included
                    $topServices = $c->filter(function ($s) {
                        $service = strtolower($s['service'] ?? '');
                        return in_array($service, ['wa', 'tg', 'fb', 'ig', 'oi', 'go', 'am', 'tn', 'ds', 'bw']);
                    });
                    
                    // Get top 200 services, then add any missing top services
                    $top200 = $c->take(200);
                    $missingTopServices = $topServices->reject(function ($service) use ($top200) {
                        return $top200->contains('service', $service['service']);
                    });
                    
                    return $top200->concat($missingTopServices)->take(210); // Slightly more than 200 to ensure top services
                })
                ->map(function ($s) use ($friendlyNames) {
                    $code = $s['service'] ?? null;
                    if ($code && isset($friendlyNames[$code])) {
                        $s['name'] = $friendlyNames[$code];
                    }
                    // Fallback: map abbreviations (<=4 chars) to friendly names across all providers
                    $nm = (string)($s['name'] ?? '');
                    if ($nm === '' || preg_match('/^[A-Z]{1,4}$/', strtoupper($nm))) {
                        if (is_string($code)) {
                            $s['name'] = $this->getServiceNameByCode($code) ?? ($nm ?: strtoupper($code));
                        }
                    }
                    return $s;
                })
                // NEW: Prioritize popular services for better user experience
                ->map(function ($s) {
                    $serviceCode = strtolower($s['service'] ?? '');
                    $serviceName = strtolower($s['name'] ?? '');
                    $fullServiceName = $serviceCode . ' ' . $serviceName; // Combine both for detection
                    
                    $s['is_popular'] = $this->isPopularService($serviceCode) || $this->isPopularService($serviceName);
                    $s['priority_score'] = $this->calculateServicePriority($fullServiceName, $s);
                    return $s;
                })
                // Convert prices to NGN using provider-specific FX/markup rules
                ->map(function ($s) use ($provider) {
                    try {
                        $prov = $s['provider'] ?? $provider ?? null;
                        $originalCost = (float)($s['cost'] ?? 0);
                        $currency = strtoupper((string)($s['currency'] ?? ''));
                        if ($prov && isset($s['cost']) && $currency !== 'NGN') {
                            $src = in_array($currency, ['USD','RUB']) ? $currency : 'USD';
                            $s['cost'] = $this->convertPriceToNgn((float)$s['cost'], (string)$prov, $src);
                            $s['currency'] = 'NGN';
                        }
                        // Safety clamp to avoid passing absurd values to frontend
                        $limits = config('services.sms_price_limits');
                        $maxNgn = (float)($limits['max_ngn'] ?? 3000000);
                        $minNgn = (float)($limits['min_ngn'] ?? 1500);
                        if (isset($s['cost'])) {
                            if ((float)$s['cost'] > $maxNgn) { $s['cost'] = $maxNgn; }
                            if ((float)$s['cost'] < $minNgn) { $s['cost'] = $minNgn; }
                        }
                    } catch (\Throwable $e) {
                        // On error, still enforce minimum price
                        $minPrice = 1500;
                        if (isset($s['cost']) && (float)$s['cost'] < $minPrice) {
                            $s['cost'] = $minPrice;
                        }
                    }
                    return $s;
                })
                // Remove duplicates and sort by priority score (popular services first)
                ->unique('service')
                ->sort(function ($a, $b) {
                    // Sort by priority score (higher = more important)
                    $aPriority = $a['priority_score'] ?? 0;
                    $bPriority = $b['priority_score'] ?? 0;
                    
                    if ($aPriority !== $bPriority) {
                        return $bPriority <=> $aPriority; // Higher priority first
                    }
                    
                    // If same priority, sort by cost (lower cost first)
                    $aCost = $a['cost'] ?? 999999;
                    $bCost = $b['cost'] ?? 999999;
                    return $aCost <=> $bCost;
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

            // PERFORMANCE: Cache the result for expensive providers (skip for DASSY)
            if ($provider && strtolower($provider) !== 'dassy') {
                Cache::put($cacheKey, $services, now()->addMinutes($cacheDuration));
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
        $requestId = Str::uuid()->toString();
        $startTime = microtime(true);
        
        $this->logSmsOperation('CREATE_ORDER_START', [
            'request_data' => $request->all(),
            'request_id' => $requestId,
        ]);
        
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:10',
            'service' => 'required|string',
            'provider' => 'nullable|string|in:5sim,dassy,tiger_sms,textverified,smspool',
            'mode' => 'nullable|string|in:auto,manual'
        ]);

        if ($validator->fails()) {
            $this->logSmsError('CREATE_ORDER_VALIDATION_FAILED', new \Exception('Validation failed'), [
                'validation_errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
                'request_id' => $requestId,
            ]);
            
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

            // We'll check balance after determining the actual service cost
            // This prevents users from purchasing services beyond their balance

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
            // Some providers (e.g., DASSY, 5SIM) don't track balance in our DB; don't block on balance for them
            if (!($provider && in_array(strtolower($provider), ['dassy','5sim']))) {
                $query->where('balance', '>', 0);
            }
            
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
                    ? "No SMS services available for provider: {$provider}. Provider may have insufficient balance."
                    : 'No SMS services available. All providers have insufficient balance.';
                    
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
            $lastError = null;
            foreach ($smsServices as $smsService) {
                try {
                    Log::info("Attempting to create order with provider", [
                        'provider' => $smsService->provider,
                        'provider_name' => $smsService->name,
                        'priority' => $smsService->priority,
                        'mode' => $mode
                    ]);
                    
                    $orderData = $this->smsProviderService->createOrder($smsService, $country, $service);

                    // Determine charge in NGN - check currency first!
                    $charge = (float)($orderData['cost'] ?? 0);
                    $orderCurrency = strtoupper((string)($orderData['currency'] ?? 'NGN'));
                    
                    // If cost is provided but in USD, convert it to NGN
                    if ($charge > 0 && $orderCurrency !== 'NGN') {
                        $src = in_array($orderCurrency, ['USD','RUB']) ? $orderCurrency : 'USD';
                        $charge = $this->convertPriceToNgn($charge, (string)$smsService->provider, $src);
                    }
                    
                    // If charge still not determined, try to get from service list
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
                                            $src = in_array($rowCurrency, ['USD','RUB']) ? $rowCurrency : 'USD';
                                            $charge = $this->convertPriceToNgn($rowCost, (string)$smsService->provider, $src);
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
                    
                    // CRITICAL: Enforce minimum price of ₦1500 BEFORE creating order
                    $minPrice = (float)(DB::table('settings')->where('key', 'sms_min_price')->value('value') ?? 1500);
                    if ($charge < $minPrice) {
                        Log::warning('SMS service price below minimum - enforcing ₦1500', [
                            'service' => $service,
                            'provider' => $smsService->provider,
                            'original_charge' => $charge,
                            'enforced_price' => $minPrice,
                            'user_id' => $user->id
                        ]);
                        $charge = $minPrice;
                    }
                    
                    $orderData['cost'] = (float) ceil($charge);
                    
                    // FINAL VALIDATION: Reject order if cost is below minimum (safety check)
                    if ($orderData['cost'] < $minPrice) {
                        Log::error('CRITICAL: Order cost below minimum - PURCHASE DECLINED', [
                            'service' => $service,
                            'provider' => $smsService->provider,
                            'cost' => $orderData['cost'],
                            'minimum_required' => $minPrice,
                            'user_id' => $user->id
                        ]);
                        
                        // Skip to next provider in auto mode, or reject in manual mode
                        if ($mode === 'manual') {
                            return response()->json([
                                'success' => false,
                                'message' => 'This service costs ₦' . number_format($orderData['cost'], 2) . ' which is below the minimum allowed price of ₦' . number_format($minPrice, 2) . '. Please select a different service or provider.'
                            ], 400);
                        }
                        // In auto mode, continue to next provider
                        continue;
                    }
                    
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

                    // Create inbox message for SMS order
                    $this->createSmsOrderInboxMessage($order);

                    // Check if user belongs to a reseller panel
                    $resellerPanel = null;
                    $platformCost = $orderData['cost']; // Original cost
                    $customerCharge = $platformCost; // What customer pays
                    $resellerCost = $platformCost; // What reseller pays to platform
                    
                    if ($user->reseller_id) {
                        $resellerPanel = \App\Models\ResellerPanel::find($user->reseller_id);
                    }

                    if ($resellerPanel && $resellerPanel->isActive()) {
                        // Calculate costs for reseller model:
                        // 1. Platform gives reseller 5% discount
                        $resellerDiscount = 0.05; // 5% discount for resellers
                        $resellerCost = $platformCost * (1 - $resellerDiscount); // Reseller pays 95% of platform price
                        
                        // 2. Reseller adds their margin to sell to customer
                        // For SMS, use the panel's SMS margin
                        $resellerMarkup = ($resellerPanel->sms_margin_percentage ?? 10) / 100;
                        $customerCharge = $platformCost * (1 + $resellerMarkup); // Customer pays platform price + reseller margin
                        
                        // 3. Check if reseller's panel wallet can afford it
                        if (!$resellerPanel->canAfford($resellerCost)) {
                            // Rollback order
                            $order->delete();
                            return response()->json([
                                'success' => false,
                                'message' => 'Panel wallet has insufficient balance. Panel balance: ₦' . number_format($resellerPanel->wallet_balance, 2) . '. Required: ₦' . number_format($resellerCost, 2) . '. Please contact the panel administrator to fund the wallet.'
                            ], 400);
                        }
                        
                        // 4. Deduct from RESELLER's panel wallet (discounted price)
                        $resellerPanel->updateWalletBalance($resellerCost, 'subtract');
                        
                        // 5. Update panel statistics with customer charge (what was sold to customer)
                        $resellerPanel->increment('total_transactions');
                        $resellerPanel->increment('total_revenue', $customerCharge);
                        
                        // 6. Update order record with actual customer charge
                        $order->update([
                            'cost' => $customerCharge,
                            'metadata' => array_merge($order->metadata ?? [], [
                                'reseller_panel_id' => $resellerPanel->id,
                                'platform_cost' => $platformCost,
                                'reseller_cost' => $resellerCost,
                                'customer_charge' => $customerCharge,
                                'reseller_margin' => $resellerMarkup * 100,
                                'platform_discount' => $resellerDiscount * 100
                            ])
                        ]);
                        
                        Log::info("Reseller purchase", [
                            'panel_id' => $resellerPanel->id,
                            'platform_cost' => $platformCost,
                            'reseller_cost' => $resellerCost,
                            'customer_charge' => $customerCharge,
                            'reseller_profit' => $customerCharge - $resellerCost,
                            'platform_profit' => $resellerCost
                        ]);
                    } else {
                        // CRITICAL: Check if user has sufficient balance for the actual service cost
                        if ($user->balance < $orderData['cost']) {
                            // Rollback order
                            $order->delete();
                            return response()->json([
                                'success' => false,
                                'message' => 'Insufficient balance. Required: ₦' . number_format($orderData['cost'], 2) . ', Available: ₦' . number_format($user->balance, 2) . '. Please recharge your account.'
                            ], 400);
                        }
                        
                        // Deduct from USER's wallet (normal flow - not a reseller customer)
                        $user->updateBalance($orderData['cost'], 'subtract');
                    }

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

                    // Deduct balance from SMS provider (in USD)
                    $providerCost = $orderCurrency === 'USD' ? $charge / 1600 : $charge / 1600; // Convert NGN back to USD
                    $smsService->deductBalance($providerCost);

                    // Update SMS service stats
                    $smsService->incrementOrders(true);
                    
                    // Check for low balance warning
                    if ($smsService->hasLowBalance(5.0)) {
                        Log::warning("Low balance alert for SMS provider", [
                            'provider' => $smsService->provider,
                            'provider_name' => $smsService->name,
                            'balance' => $smsService->balance,
                            'threshold' => 5.0
                        ]);
                    }

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
                    $lastError = $e->getMessage();
                    \Log::error("Failed to create order with {$smsService->name}: " . $e->getMessage(), [
                        'provider' => $smsService->provider,
                        'country' => $country,
                        'service' => $service,
                        'mode' => $mode,
                    ]);
                    continue;
                }
            }

            $errorMessage = $mode === 'manual' 
                ? (is_string($lastError) && $lastError !== '' ? $lastError : "Failed to create SMS order. Please try again later.")
                : (is_string($lastError) && $lastError !== '' ? $lastError : 'Failed to create SMS order. All providers are currently unavailable.');

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
        // Be flexible with input: accept order_id | reference | id
        $incomingOrderId = $request->input('order_id')
            ?? $request->input('reference')
            ?? $request->input('id')
            ?? $request->query('order_id');

        if ($incomingOrderId) {
            // Normalize into order_id for validation/query
            $request->merge(['order_id' => (string)$incomingOrderId]);
        }

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
            $orderId = (string)$request->order_id;

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
                // Mark expired and refund user automatically
                $order->markAsExpired();
                try {
                    $user->updateBalance($order->cost, 'add');
                    // Create refund transaction
                    $user->transactions()->create([
                        'type' => 'refund',
                        'amount' => $order->cost,
                        'balance_before' => $user->balance - $order->cost,
                        'balance_after' => $user->balance,
                        'description' => "Refund for expired SMS order {$order->order_id}",
                        'reference' => 'REF_' . Str::random(15),
                        'status' => 'success',
                        'metadata' => [
                            'order_id' => $order->order_id,
                            'reason' => 'expired_no_sms',
                        ]
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to create refund transaction for expired order', [
                        'order_id' => $order->order_id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Order has expired. Amount refunded to your wallet.'
                ], 400);
            }

            // Get SMS code from provider
            try {
                $smsCode = $this->smsProviderService->getSmsCode($order->smsService, $order->provider_order_id);
            } catch (\Throwable $e) {
                $msg = strtoupper($e->getMessage());
                // Handle provider-level cancellations/expirations with refund
                if (str_contains($msg, 'CANCEL') || str_contains($msg, 'EXPIRED') || str_contains($msg, 'STATUS_CANCEL')) {
                    $order->markAsCancelled();
                    try {
                        $user->updateBalance($order->cost, 'add');
                        $user->transactions()->create([
                            'type' => 'refund',
                            'amount' => $order->cost,
                            'balance_before' => $user->balance - $order->cost,
                            'balance_after' => $user->balance,
                            'description' => "Refund for cancelled/expired SMS order {$order->order_id}",
                            'reference' => 'REF_' . Str::random(15),
                            'status' => 'success',
                            'metadata' => [
                                'order_id' => $order->order_id,
                                'reason' => 'provider_cancelled_or_expired',
                            ]
                        ]);
                    } catch (\Throwable $ex) {
                        Log::warning('Failed to refund on provider cancel', [
                            'order_id' => $order->order_id,
                            'error' => $ex->getMessage()
                        ]);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Order cancelled/expired by provider. Amount refunded to your wallet.'
                    ], 400);
                }
                throw $e; // other errors
            }

            if ($smsCode) {
                $order->markAsCompleted($smsCode);
                
                // Update inbox message with SMS code
                $this->updateSmsOrderInboxMessage($order);
                
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

            // If already cancelled, return idempotent success (avoid double refunds)
            if (strtolower((string)$order->status) === 'cancelled') {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already cancelled.'
                ]);
            }

            // If order is completed, do not allow cancellation
            if ($order->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled'
                ], 400);
            }

            // If order is expired but no SMS code was received, refund immediately
            if ($order->isExpired() && empty($order->sms_code)) {
                $order->markAsCancelled();
                try {
                    // Refund user balance
                    $user->updateBalance($order->cost, 'add');
                    // Create refund transaction
                    $user->transactions()->create([
                        'type' => 'refund',
                        'amount' => $order->cost,
                        'balance_before' => $user->balance - $order->cost,
                        'balance_after' => $user->balance,
                        'description' => "Refund for expired SMS order {$order->order_id}",
                        'reference' => 'REF_' . Str::random(15),
                        'status' => 'success',
                        'metadata' => [
                            'order_id' => $order->order_id,
                            'reason' => 'expired_no_sms_cancel',
                        ]
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('CancelOrder: failed to create refund transaction for expired order', [
                        'order_id' => $order->order_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Order expired without SMS. Balance has been refunded.'
                ]);
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
    /**
     * Create inbox message for SMS order
     */
    private function createSmsOrderInboxMessage(SmsOrder $order): void
    {
        try {
            $serviceName = $order->getServiceDisplayName();
            $phoneNumber = $order->getFormattedPhoneNumber();
            
            $inboxMessage = \App\Models\InboxMessage::create([
                'user_id' => $order->user_id,
                'type' => 'sms_order',
                'title' => "Fadded VIP 🔆  SMS Order - {$serviceName}",
                'message' => "Your virtual number {$phoneNumber} for {$serviceName} is ready. Waiting for SMS verification code to arrive.",
                'reference' => $order->order_id,
                'metadata' => [
                    'order_id' => $order->order_id,
                    'phone_number' => $order->phone_number,
                    'formatted_phone' => $phoneNumber,
                    'service' => $order->service,
                    'service_name' => $serviceName,
                    'country' => $order->country,
                    'cost' => $order->cost,
                    'status' => $order->status,
                    'provider' => $order->smsService->provider ?? 'unknown',
                    'provider_name' => $order->smsService->name ?? 'Unknown Provider'
                ],
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            \Log::info('SMS order inbox message created', [
                'order_id' => $order->order_id,
                'inbox_message_id' => $inboxMessage->id,
                'user_id' => $order->user_id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create SMS order inbox message', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update inbox message when SMS code is received
     */
    private function updateSmsOrderInboxMessage(SmsOrder $order): void
    {
        try {
            $inboxMessage = \App\Models\InboxMessage::where('user_id', $order->user_id)
                ->where('reference', $order->order_id)
                ->where('type', 'sms_order')
                ->first();
                
            if ($inboxMessage) {
                $serviceName = $order->getServiceDisplayName();
                $phoneNumber = $order->getFormattedPhoneNumber();
                
                $inboxMessage->update([
                    'title' => "Fadded VIP 🔆  SMS Received - {$serviceName}",
                    'message' => "SMS verification code received for {$phoneNumber} ({$serviceName}). Code: {$order->sms_code}",
                    'metadata' => array_merge($inboxMessage->metadata ?? [], [
                        'sms_code' => $order->sms_code,
                        'status' => $order->status,
                        'received_at' => $order->received_at?->toISOString()
                    ]),
                    'updated_at' => now()
                ]);
                
                \Log::info('SMS order inbox message updated with code', [
                    'order_id' => $order->order_id,
                    'inbox_message_id' => $inboxMessage->id,
                    'sms_code' => $order->sms_code
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to update SMS order inbox message', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
