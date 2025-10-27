<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SmsService;
use App\Services\SmsProviderService;

class RefreshSmsCatalog extends Command
{
    protected $signature = 'sms:refresh-catalog {--mode=hot : hot|full} {--provider=* : Limit to one or more providers}';
    protected $description = 'Refresh SMS catalog (countries/services/prices) into catalog tables';

    private array $hotCountries = ['US','GB','CA','AU','DE','FR','IT','ES','NL','SE','NO','DK','FI','PL','CZ','HU','AT','CH','BE','IE','PT','GR','NG','IN','KE','ZA'];
    private array $defaultProviders = ['smspool','5sim','dassy','tiger_sms'];

    public function handle()
    {
        $mode = strtolower((string)$this->option('mode')) ?: 'hot';
        $providers = $this->option('provider');
        if (!is_array($providers) || empty($providers)) {
            $providers = $this->defaultProviders;
        }

        $this->info("🔄 Refreshing SMS catalog (mode={$mode}) for providers: " . implode(',', $providers));

        $svc = app(SmsProviderService::class);
        $totalCountries = 0; $totalServices = 0; $errors = 0;

        foreach ($providers as $provider) {
            $this->info("📡 Provider: {$provider}");
            $model = SmsService::where('provider', $provider)->where('is_active', true)->first();
            if (!$model) { $this->warn("  ❌ Provider not configured/active: {$provider}"); continue; }

            // Determine country list
            $countries = [];
            if ($mode === 'hot') {
                $countries = $this->hotCountries;
            } else {
                // Full mode: use cached catalog if available, else probe provider
                $rows = DB::table('sms_country_catalog')->where('provider', $provider)->get(['country_code']);
                if ($rows->isNotEmpty()) {
                    $countries = $rows->pluck('country_code')->map(fn($c) => strtoupper((string)$c))->unique()->values()->all();
                } else {
                    // Probe provider once to seed catalog
                    try {
                        $list = $svc->getCountries($model);
                        foreach ($list as $c) {
                            $code = strtoupper((string)($c['code'] ?? $c['country'] ?? ''));
                            $name = (string)($c['name'] ?? $c['country_name'] ?? '');
                            if ($code && $name) {
                                $countries[] = $code;
                                try {
                                    DB::table('sms_country_catalog')->updateOrInsert(
                                        [ 'provider' => $provider, 'country_code' => $code ],
                                        [ 'country_name' => $name, 'updated_at' => now(), 'created_at' => now() ]
                                    );
                                } catch (\Throwable $e) {}
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->error("   ❌ Failed to list countries: {$e->getMessage()}");
                        $errors++;
                        continue;
                    }
                }
            }

            // Refresh services for each country (popular-first order)
            $countries = array_values(array_unique(array_map('strtoupper', $countries)));
            foreach ($countries as $cc) {
                try {
                    $services = $svc->getServices($model, $cc);
                    $totalCountries++;
                    $totalServices += count($services);

                    // Upsert country catalog name if missing
                    try {
                        $exists = DB::table('sms_country_catalog')->where('provider',$provider)->where('country_code',$cc)->exists();
                        if (!$exists) {
                            DB::table('sms_country_catalog')->updateOrInsert(
                                [ 'provider' => $provider, 'country_code' => $cc ],
                                [ 'country_name' => $cc, 'updated_at' => now(), 'created_at' => now() ]
                            );
                        }
                    } catch (\Throwable $e) {}

                    // Upsert price map
                    $priceCache = app(\App\Services\Sms\PriceCacheService::class);
                    $priceCache->upsertPrices($provider, $cc, $services);

                    // Tiny delay to avoid rate limiting
                    usleep(80000); // 80ms
                } catch (\Throwable $e) {
                    $errors++;
                    Log::warning('RefreshSmsCatalog country failed', ['provider'=>$provider,'country'=>$cc,'error'=>$e->getMessage()]);
                }
            }
        }

        $this->info("✅ Done. Countries: {$totalCountries}, Services: {$totalServices}, Errors: {$errors}");
        Log::info('RefreshSmsCatalog completed', ['countries'=>$totalCountries,'services'=>$totalServices,'errors'=>$errors,'mode'=>$mode,'providers'=>$providers]);
    }
}


