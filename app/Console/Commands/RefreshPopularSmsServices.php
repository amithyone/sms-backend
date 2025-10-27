<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SmsService;
use App\Services\SmsProviderService;

class RefreshPopularSmsServices extends Command
{
    protected $signature = 'sms:refresh-popular-services {--country=} {--provider=}';
    protected $description = 'Refresh popular SMS services to ensure they are always available and cached';

    private $popularServices = [
        'whatsapp', 'wa', 'telegram', 'tg', 'google', 'facebook', 'instagram',
        'tiktok', 'twitter', 'snapchat', 'discord', 'microsoft', 'apple',
        'uber', 'paypal', 'amazon', 'netflix', 'spotify', 'zoom', 'slack'
    ];

    private $priorityCountries = [
        'us', 'gb', 'ca', 'au', 'de', 'fr', 'it', 'es', 'nl', 'se', 'no',
        'dk', 'fi', 'pl', 'cz', 'hu', 'at', 'ch', 'be', 'ie', 'pt', 'gr'
    ];

    public function handle()
    {
        $this->info('🔄 Refreshing popular SMS services...');
        
        $country = $this->option('country');
        $provider = $this->option('provider');
        
        $countries = $country ? [$country] : $this->priorityCountries;
        $providers = $provider ? [$provider] : ['5sim', 'smspool', 'dassy', 'tiger_sms'];
        
        $totalRefreshed = 0;
        $totalErrors = 0;
        
        foreach ($providers as $providerName) {
            $this->info("📡 Processing provider: {$providerName}");
            
            $smsService = SmsService::where('provider', $providerName)
                ->where('is_active', true)
                ->first();
                
            if (!$smsService) {
                $this->warn("  ❌ Provider {$providerName} not found or inactive");
                continue;
            }
            
            foreach ($countries as $countryCode) {
                try {
                    $refreshed = $this->refreshProviderCountryServices($smsService, $countryCode);
                    $totalRefreshed += $refreshed;
                    
                    if ($refreshed > 0) {
                        $this->info("  ✅ {$countryCode}: {$refreshed} services refreshed");
                    }
                    
                    // Small delay to avoid rate limiting
                    usleep(100000); // 100ms
                    
                } catch (\Exception $e) {
                    $totalErrors++;
                    $this->error("  ❌ {$countryCode}: {$e->getMessage()}");
                    Log::error('Failed to refresh SMS services', [
                        'provider' => $providerName,
                        'country' => $countryCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Clear cache to ensure fresh data
        $this->call('cache:clear');
        
        $this->info('');
        $this->info("✅ Refresh completed!");
        $this->info("   📊 Total services refreshed: {$totalRefreshed}");
        $this->info("   ❌ Total errors: {$totalErrors}");
        
        // Log summary
        Log::info('SMS popular services refresh completed', [
            'total_refreshed' => $totalRefreshed,
            'total_errors' => $totalErrors,
            'providers' => $providers,
            'countries' => $countries
        ]);
    }
    
    private function refreshProviderCountryServices(SmsService $smsService, string $countryCode): int
    {
        $smsProviderService = app(SmsProviderService::class);
        
        // Get services from provider
        $services = $smsProviderService->getServices($smsService, $countryCode);
        
        if (empty($services)) {
            return 0;
        }
        
        // Filter for popular services only
        $popularServices = collect($services)->filter(function ($service) {
            $serviceName = strtolower($service['service'] ?? '');
            foreach ($this->popularServices as $popular) {
                if (strpos($serviceName, $popular) !== false) {
                    return true;
                }
            }
            return false;
        });
        
        if ($popularServices->isEmpty()) {
            return 0;
        }
        
        // Update cache with popular services
        $priceCache = app(\App\Services\Sms\PriceCacheService::class);
        $priceCache->upsertPrices(
            $smsService->provider,
            $countryCode,
            $popularServices->toArray()
        );
        
        return $popularServices->count();
    }
}
