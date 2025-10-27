<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PriceCacheService
{
    /**
     * Upsert provider service prices for a given country into sms_service_country_prices
     *
     * @param string $provider e.g. 'smspool', '5sim'
     * @param string $countryCode ISO like 'NG' or numeric like '1'
     * @param array<int,array<string,mixed>> $services Each with keys: service, name, cost, count
     */
    public function upsertPrices(string $provider, string $countryCode, array $services): void
    {
        $providerCurrencyDefault = $this->inferProviderCurrency($provider);
        $now = now();

        foreach ($services as $row) {
            if (!is_array($row)) { continue; }
            $serviceCode = (string)($row['service'] ?? '');
            if ($serviceCode === '') { continue; }

            $serviceName = (string)($row['name'] ?? '');
            $cost = (float)($row['cost'] ?? 0);
            $count = (int)($row['count'] ?? 0);

            try {
                // Prefer currency from row if provided; otherwise infer
                $rowCurrency = strtoupper((string)($row['currency'] ?? ''));
                $providerCurrency = $rowCurrency !== '' ? $rowCurrency : $providerCurrencyDefault;
                DB::table('sms_service_country_prices')->updateOrInsert(
                    [
                        'provider' => $provider,
                        'service' => $serviceCode,
                        'country_code' => (string)$countryCode,
                    ],
                    [
                        'service_name' => $serviceName,
                        'cost' => $cost,
                        'count' => $count,
                        'provider_currency' => $providerCurrency,
                        'last_seen_at' => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                // Also upsert friendly name mapping so UI can show names for numeric codes (e.g., smspool)
                if ($serviceName !== '') {
                    try {
                        DB::table('sms_service_codes')->updateOrInsert(
                            [ 'provider' => $provider, 'code' => $serviceCode ],
                            [ 'name' => $serviceName, 'updated_at' => $now, 'created_at' => $now ]
                        );
                    } catch (\Throwable $e) {
                        Log::warning('PriceCache upsert code-name failed', [
                            'provider' => $provider,
                            'service' => $serviceCode,
                            'name' => $serviceName,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('PriceCache upsert failed', [
                    'provider' => $provider,
                    'country' => $countryCode,
                    'service' => $serviceCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function inferProviderCurrency(string $provider): string
    {
        // Provider-native currency hints for catalog rows
        switch (strtolower($provider)) {
            case 'dassy':
                return 'RUB';
            case 'textverified':
                return 'USD';
            default:
                return 'USD';
        }
    }
}


