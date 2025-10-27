<?php

namespace App\Services;

use App\Models\CachedSmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SmsCacheService
{
    private $providers = ['tiger_sms', '5sim', 'dassy', 'smspool', 'textverified'];
    private $baseUrl = 'https://api.fadsms.com/api';
    private $minimumPrice = 1500; // Minimum price in NGN
    private $fxRate = 1600; // USD to NGN conversion rate

    /**
     * Update all cached services from all providers
     */
    public function updateAllServices()
    {
        Log::info('🔄 Starting SMS services cache update');
        
        $totalUpdated = 0;
        $totalErrors = 0;
        $totalSkipped = 0;

        // Get comprehensive list of countries to try
        $allCountries = $this->getComprehensiveCountryList();
        
        Log::info("🌍 Found " . count($allCountries) . " countries to process");

        // Try each provider for each country
        foreach ($allCountries as $country) {
            foreach ($this->providers as $provider) {
                try {
                    // First, validate if provider supports this country
                    if (!$this->validateProviderCountrySupport($provider, $country['code'])) {
                        $totalSkipped++;
                        Log::info("⏭️ Skipping {$provider} - {$country['name']} (country not supported)");
                        continue;
                    }
                    
                    $services = $this->getProviderServicesForCountry($provider, $country['code']);
                    if (!empty($services)) {
                        $this->saveServicesToCache($provider, $country, $services);
                        $totalUpdated += count($services);
                        Log::info("💾 Cached " . count($services) . " services for {$provider} - {$country['name']}");
                    }
                } catch (\Exception $e) {
                    $totalErrors++;
                    Log::warning("⚠️ Failed to fetch services for {$provider} - {$country['name']}: " . $e->getMessage());
                }
            }
        }

        // Clean up old data (older than 2 hours)
        $this->cleanupOldData();

        Log::info("🎯 Cache update completed: {$totalUpdated} services updated, {$totalErrors} errors, {$totalSkipped} unsupported countries skipped");
        
        return [
            'updated' => $totalUpdated,
            'errors' => $totalErrors,
            'skipped' => $totalSkipped,
            'providers' => $this->providers
        ];
    }

    /**
     * Update services for a specific provider
     */
    private function updateProviderServices($provider)
    {
        Log::info("📡 Fetching services for provider: {$provider}");

        // Get countries for this provider
        $countries = $this->getProviderCountries($provider);
        
        $totalServices = 0;

        foreach ($countries as $country) {
            try {
                $services = $this->getProviderServicesForCountry($provider, $country['code']);
                if (!empty($services)) {
                    $this->saveServicesToCache($provider, $country, $services);
                    $totalServices += count($services);
                    
                    Log::info("💾 Cached " . count($services) . " services for {$provider} - {$country['name']}");
                } else {
                    Log::info("📭 No services returned for {$provider} - {$country['name']}");
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Failed to fetch services for {$provider} - {$country['name']}: " . $e->getMessage());
            }
        }

        return $totalServices;
    }

    /**
     * Get comprehensive list of countries to try with all providers
     */
    private function getComprehensiveCountryList()
    {
        return [
            // Major countries with ISO2 codes
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'FI', 'name' => 'Finland'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'KR', 'name' => 'South Korea'],
            ['code' => 'CN', 'name' => 'China'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
            ['code' => 'TR', 'name' => 'Turkey'],
            ['code' => 'SA', 'name' => 'Saudi Arabia'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'RU', 'name' => 'Russia'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'PE', 'name' => 'Peru'],
            ['code' => 'VE', 'name' => 'Venezuela'],
            ['code' => 'EG', 'name' => 'Egypt'],
            ['code' => 'MA', 'name' => 'Morocco'],
            ['code' => 'KE', 'name' => 'Kenya'],
            ['code' => 'GH', 'name' => 'Ghana'],
            ['code' => 'ET', 'name' => 'Ethiopia'],
            ['code' => 'TZ', 'name' => 'Tanzania'],
            ['code' => 'UG', 'name' => 'Uganda'],
            ['code' => 'TH', 'name' => 'Thailand'],
            ['code' => 'VN', 'name' => 'Vietnam'],
            ['code' => 'ID', 'name' => 'Indonesia'],
            ['code' => 'MY', 'name' => 'Malaysia'],
            ['code' => 'SG', 'name' => 'Singapore'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'PK', 'name' => 'Pakistan'],
            ['code' => 'BD', 'name' => 'Bangladesh'],
            ['code' => 'LK', 'name' => 'Sri Lanka'],
            ['code' => 'NP', 'name' => 'Nepal'],
            ['code' => 'MM', 'name' => 'Myanmar'],
            ['code' => 'KH', 'name' => 'Cambodia'],
            ['code' => 'LA', 'name' => 'Laos'],
            ['code' => 'MN', 'name' => 'Mongolia'],
            ['code' => 'KZ', 'name' => 'Kazakhstan'],
            ['code' => 'UZ', 'name' => 'Uzbekistan'],
            ['code' => 'KG', 'name' => 'Kyrgyzstan'],
            ['code' => 'TJ', 'name' => 'Tajikistan'],
            ['code' => 'TM', 'name' => 'Turkmenistan'],
            ['code' => 'AF', 'name' => 'Afghanistan'],
            ['code' => 'IR', 'name' => 'Iran'],
            ['code' => 'IQ', 'name' => 'Iraq'],
            ['code' => 'SY', 'name' => 'Syria'],
            ['code' => 'LB', 'name' => 'Lebanon'],
            ['code' => 'JO', 'name' => 'Jordan'],
            ['code' => 'IL', 'name' => 'Israel'],
            ['code' => 'PS', 'name' => 'Palestine'],
            ['code' => 'KW', 'name' => 'Kuwait'],
            ['code' => 'QA', 'name' => 'Qatar'],
            ['code' => 'BH', 'name' => 'Bahrain'],
            ['code' => 'OM', 'name' => 'Oman'],
            ['code' => 'YE', 'name' => 'Yemen'],
            ['code' => 'CY', 'name' => 'Cyprus'],
            ['code' => 'MT', 'name' => 'Malta'],
            ['code' => 'IS', 'name' => 'Iceland'],
            ['code' => 'IE', 'name' => 'Ireland'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'GR', 'name' => 'Greece'],
            ['code' => 'BG', 'name' => 'Bulgaria'],
            ['code' => 'RO', 'name' => 'Romania'],
            ['code' => 'HU', 'name' => 'Hungary'],
            ['code' => 'CZ', 'name' => 'Czech Republic'],
            ['code' => 'SK', 'name' => 'Slovakia'],
            ['code' => 'SI', 'name' => 'Slovenia'],
            ['code' => 'HR', 'name' => 'Croatia'],
            ['code' => 'BA', 'name' => 'Bosnia and Herzegovina'],
            ['code' => 'RS', 'name' => 'Serbia'],
            ['code' => 'ME', 'name' => 'Montenegro'],
            ['code' => 'MK', 'name' => 'North Macedonia'],
            ['code' => 'AL', 'name' => 'Albania'],
            ['code' => 'XK', 'name' => 'Kosovo'],
            ['code' => 'MD', 'name' => 'Moldova'],
            ['code' => 'UA', 'name' => 'Ukraine'],
            ['code' => 'BY', 'name' => 'Belarus'],
            ['code' => 'LT', 'name' => 'Lithuania'],
            ['code' => 'LV', 'name' => 'Latvia'],
            ['code' => 'EE', 'name' => 'Estonia'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'AT', 'name' => 'Austria'],
            ['code' => 'BE', 'name' => 'Belgium'],
            ['code' => 'LU', 'name' => 'Luxembourg'],
            ['code' => 'LI', 'name' => 'Liechtenstein'],
            ['code' => 'MC', 'name' => 'Monaco'],
            ['code' => 'SM', 'name' => 'San Marino'],
            ['code' => 'VA', 'name' => 'Vatican City'],
            ['code' => 'AD', 'name' => 'Andorra'],
            // Alternative country codes that might be used by providers
            ['code' => 'USA', 'name' => 'United States'],
            ['code' => 'UK', 'name' => 'United Kingdom'],
            ['code' => 'england', 'name' => 'United Kingdom'],
            ['code' => 'germany', 'name' => 'Germany'],
            ['code' => 'france', 'name' => 'France'],
            ['code' => 'italy', 'name' => 'Italy'],
            ['code' => 'spain', 'name' => 'Spain'],
            ['code' => 'canada', 'name' => 'Canada'],
            ['code' => 'australia', 'name' => 'Australia'],
            ['code' => 'nigeria', 'name' => 'Nigeria'],
            ['code' => 'kenya', 'name' => 'Kenya'],
            ['code' => 'indonesia', 'name' => 'Indonesia'],
            ['code' => 'mexico', 'name' => 'Mexico'],
        ];
    }

    /**
     * Get countries available for a provider
     */
    private function getProviderCountries($provider)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/sms/countries", [
                'provider' => $provider
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && isset($data['data'])) {
                    return collect($data['data'])->map(function ($country) {
                        return [
                            'code' => $country['code2'] ?? $country['code'] ?? $country['id'],
                            'name' => $country['name'] ?? $country['title'] ?? $country['country_name']
                        ];
                    })->toArray();
                }
            }
        } catch (\Exception $e) {
            Log::warning("⚠️ Failed to get countries for {$provider}: " . $e->getMessage());
        }

        // Fallback to common countries
        return [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'ES', 'name' => 'Spain'],
        ];
    }

    /**
     * Get services for a provider and country
     */
    private function getProviderServicesForCountry($provider, $countryCode)
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/sms/services", [
                'country' => $countryCode,
                'provider' => $provider,
                '_t' => time() // Cache busting
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && isset($data['data'])) {
                    return $data['data'];
                }
            }
        } catch (\Exception $e) {
            Log::warning("⚠️ Failed to get services for {$provider} - {$countryCode}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Save services to cache with proper normalization
     */
    private function saveServicesToCache($provider, $country, $services)
    {
        $providerName = $this->getProviderDisplayName($provider);
        $now = now();

        foreach ($services as $service) {
            try {
                // Normalize service data
                $serviceCode = $service['service'] ?? $service['code'] ?? strtolower($service['name'] ?? '');
                $serviceName = $service['name'] ?? $service['service_name'] ?? ucfirst($serviceCode);
                
                // Calculate final NGN price
                $originalCost = floatval($service['cost'] ?? $service['price'] ?? 0);
                $originalCurrency = $service['currency'] ?? 'USD';
                
                $costNgn = $this->calculateNgnPrice($originalCost, $originalCurrency);
                
                // Apply minimum price rule
                if ($costNgn < $this->minimumPrice) {
                    $costNgn = $this->minimumPrice;
                }

                // Determine if service is popular
                $isPopular = $this->isPopularService($serviceName, $serviceCode);

                // Save to cache
                CachedSmsService::updateOrCreate(
                    [
                        'provider' => $provider,
                        'country_code' => $country['code'],
                        'service_code' => $serviceCode,
                    ],
                    [
                        'provider_name' => $providerName,
                        'brand_name' => $providerName, // Store brand name in dedicated column
                        'country_name' => $country['name'],
                        'service_name' => $serviceName,
                        'cost_ngn' => $costNgn,
                        'original_cost' => $originalCost,
                        'original_currency' => $originalCurrency,
                        'available_count' => intval($service['count'] ?? $service['available'] ?? 0),
                        'is_popular' => $isPopular,
                        'status' => 'active',
                        'metadata' => $service,
                        'last_updated' => $now,
                    ]
                );
            } catch (\Exception $e) {
                Log::warning("⚠️ Failed to save service {$service['name']} for {$provider}: " . $e->getMessage());
            }
        }
    }

    /**
     * Calculate NGN price from original cost and currency
     */
    private function calculateNgnPrice($originalCost, $currency)
    {
        if ($currency === 'NGN') {
            return $originalCost;
        }

        if ($currency === 'USD') {
            return $originalCost * $this->fxRate;
        }

        // For other currencies, assume USD rate
        return $originalCost * $this->fxRate;
    }

    /**
     * Check if service is popular
     */
    private function isPopularService($serviceName, $serviceCode)
    {
        $popularServices = ['whatsapp', 'telegram', 'signal', 'tinder', 'google', 'facebook', 'instagram', 'twitter'];
        
        $name = strtolower($serviceName);
        $code = strtolower($serviceCode);
        
        foreach ($popularServices as $popular) {
            if (strpos($name, $popular) !== false || strpos($code, $popular) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get provider display name from SMS services table
     */
    private function getProviderDisplayName($provider)
    {
        // Fetch the actual brand name from SMS services table
        $smsService = \DB::table('sms_services')
            ->where('provider', $provider)
            ->select('name')
            ->first();
            
        if ($smsService) {
            return $smsService->name;
        }
        
        // Fallback to hardcoded names if not found in SMS services table
        $names = [
            'tiger_sms' => 'Tiger SMS',
            '5sim' => '5SIM',
            'dassy' => 'Dassy SMS',
            'smspool' => 'SMSPool',
            'textverified' => 'TextVerified',
        ];

        return $names[$provider] ?? ucfirst(str_replace('_', ' ', $provider));
    }

    /**
     * Clean up old cached data
     */
    private function cleanupOldData()
    {
        $deleted = CachedSmsService::where('last_updated', '<', now()->subHours(3))->delete();
        Log::info("🧹 Cleaned up {$deleted} old cached services");
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        return [
            'total_services' => CachedSmsService::count(),
            'active_services' => CachedSmsService::where('status', 'active')->count(),
            'providers' => CachedSmsService::select('provider')
                ->selectRaw('COUNT(*) as service_count')
                ->selectRaw('MAX(last_updated) as last_updated')
                ->groupBy('provider')
                ->get(),
            'countries' => CachedSmsService::select('country_code', 'country_name')
                ->selectRaw('COUNT(*) as service_count')
                ->groupBy('country_code', 'country_name')
                ->orderBy('service_count', 'desc')
                ->limit(10)
                ->get(),
            'last_updated' => CachedSmsService::max('last_updated'),
        ];
    }

    /**
     * Validate if a provider supports a specific country
     */
    private function validateProviderCountrySupport(string $provider, string $countryCode): bool
    {
        // Define provider country support mappings
        $providerCountrySupport = [
            '5sim' => [
                // 5SIM is currently down (Cloudflare blocked) - no countries supported
            ],
            'tiger_sms' => [
                // Tiger SMS supports all these countries but currently has NO_BALANCE
                // TODO: Fund Tiger SMS to enable all countries
                'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'NO', 'DK', 'FI',
                'PL', 'CZ', 'HU', 'AT', 'CH', 'BE', 'IE', 'PT', 'GR', 'RU', 'UA', 'BY', 'MD',
                'TR', 'IL', 'JO', 'LB', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'YE', 'AF', 'PK',
                'IN', 'BD', 'LK', 'NP', 'BT', 'MV', 'CN', 'JP', 'KR', 'KP', 'MN', 'TW', 'HK',
                'MO', 'TH', 'VN', 'KH', 'LA', 'MM', 'MY', 'SG', 'ID', 'PH', 'BN', 'TL', 'PG',
                'NZ', 'FJ', 'SB', 'VU', 'WS', 'TO', 'KI', 'TV', 'NR', 'PW', 'FM', 'MH',
                // Also include full country names
                'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Italy', 'Spain', 'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Finland',
                'Poland', 'Czech Republic', 'Hungary', 'Austria', 'Switzerland', 'Belgium', 'Ireland', 'Portugal', 'Greece', 'Russia', 'Ukraine', 'Belarus', 'Moldova',
                'Turkey', 'Israel', 'Jordan', 'Lebanon', 'Saudi Arabia', 'United Arab Emirates', 'Kuwait', 'Qatar', 'Bahrain', 'Oman', 'Yemen', 'Afghanistan', 'Pakistan',
                'India', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Bhutan', 'Maldives', 'China', 'Japan', 'South Korea', 'North Korea', 'Mongolia', 'Taiwan', 'Hong Kong',
                'Macau', 'Thailand', 'Vietnam', 'Cambodia', 'Laos', 'Myanmar', 'Malaysia', 'Singapore', 'Indonesia', 'Philippines', 'Brunei', 'East Timor', 'Papua New Guinea',
                'New Zealand', 'Fiji', 'Solomon Islands', 'Vanuatu', 'Samoa', 'Tonga', 'Kiribati', 'Tuvalu', 'Nauru', 'Palau', 'Micronesia', 'Marshall Islands'
            ],
            'dassy' => [
                'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'NO', 'DK', 'FI',
                'PL', 'CZ', 'HU', 'AT', 'CH', 'BE', 'IE', 'PT', 'GR', 'RU', 'UA', 'BY', 'MD',
                'TR', 'IL', 'JO', 'LB', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'YE', 'AF', 'PK',
                'IN', 'BD', 'LK', 'NP', 'BT', 'MV', 'CN', 'JP', 'KR', 'KP', 'MN', 'TW', 'HK',
                'MO', 'TH', 'VN', 'KH', 'LA', 'MM', 'MY', 'SG', 'ID', 'PH', 'BN', 'TL', 'PG',
                'NZ', 'FJ', 'SB', 'VU', 'WS', 'TO', 'KI', 'TV', 'NR', 'PW', 'FM', 'MH',
                // Also include full country names
                'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Italy', 'Spain', 'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Finland',
                'Poland', 'Czech Republic', 'Hungary', 'Austria', 'Switzerland', 'Belgium', 'Ireland', 'Portugal', 'Greece', 'Russia', 'Ukraine', 'Belarus', 'Moldova',
                'Turkey', 'Israel', 'Jordan', 'Lebanon', 'Saudi Arabia', 'United Arab Emirates', 'Kuwait', 'Qatar', 'Bahrain', 'Oman', 'Yemen', 'Afghanistan', 'Pakistan',
                'India', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Bhutan', 'Maldives', 'China', 'Japan', 'South Korea', 'North Korea', 'Mongolia', 'Taiwan', 'Hong Kong',
                'Macau', 'Thailand', 'Vietnam', 'Cambodia', 'Laos', 'Myanmar', 'Malaysia', 'Singapore', 'Indonesia', 'Philippines', 'Brunei', 'East Timor', 'Papua New Guinea',
                'New Zealand', 'Fiji', 'Solomon Islands', 'Vanuatu', 'Samoa', 'Tonga', 'Kiribati', 'Tuvalu', 'Nauru', 'Palau', 'Micronesia', 'Marshall Islands'
            ],
            'smspool' => [
                // SMSPool only supports these specific countries (tested)
                'US', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'PL', 'CZ', 'GR', 'UA', 
                'CN', 'JP', 'HK', 'MO', 'LA', 'ID',
                // Also include full country names that might be used
                'United States', 'United Kingdom', 'Australia', 'Germany', 'France', 'Italy', 
                'Spain', 'Netherlands', 'Sweden', 'Poland', 'Czech Republic', 'Greece', 'Ukraine',
                'China', 'Japan', 'Hong Kong', 'Macau', 'Laos', 'Indonesia'
            ],
            'textverified' => [
                'US', // TextVerified only supports US
                'United States' // Also include full country name
            ]
        ];

        return in_array($countryCode, $providerCountrySupport[$provider] ?? []);
    }
}
