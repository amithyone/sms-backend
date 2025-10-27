<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsService;
use App\Services\SimpleHttpClient;
use App\Services\Sms\ProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class DassyProvider implements ProviderInterface
{
    private SimpleHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = (new SimpleHttpClient())->timeout(30);
    }

    public function getCountries(SmsService $smsService): array
    {
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $data = $response->json();
            if (is_array($data)) {
                $countries = [];
                foreach ($data as $countryCode => $services) {
                    if (is_array($services) && !empty($services)) {
                        [$iso2, $friendly] = $this->mapCountry((string)$countryCode);
                        $countries[] = [
                            'code' => (string)$countryCode,   // Daisy expects numeric code in subsequent calls
                            'name' => $friendly,               // Friendly display name for frontend
                            'code2' => $iso2,                  // Optional ISO2 for consumers that need it
                        ];
                    }
                }
                return $countries;
            }
        }
        Log::error('DaisySMS getCountries failed', [ 'url' => $url, 'status' => $response->status(), 'body_sample' => substr($response->body(), 0, 300) ]);
        return [];
    }

    public function getServices(SmsService $smsService, string $country): array
    {
        // DASSY only supports US (187) - reject all other countries
        $dassyCountryCode = $this->mapFrontendToDassyCountry($country);
        Log::info('DASSY getServices called', ['country' => $country, 'mapped' => $dassyCountryCode]);
        if ($dassyCountryCode !== '187') {
            Log::info('DASSY rejecting non-US country', ['country' => $country, 'mapped' => $dassyCountryCode]);
            return [];
        }
        
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getPrices';
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $data = $response->json();
            
            if (is_array($data) && isset($data[$dassyCountryCode])) {
                $services = [];
                foreach ($data[$dassyCountryCode] as $serviceCode => $serviceData) {
                    if (is_array($serviceData) && isset($serviceData['cost'])) {
                        $usd = (float)$serviceData['cost'];
                        // IMPORTANT: Return RAW USD. Controller will convert once.
                        $services[] = [
                            'service' => $serviceCode,
                            'name' => strtoupper($serviceCode),
                            'cost' => $usd,
                            'count' => (int)($serviceData['count'] ?? 0),
                            // Daisy/SMS-activate style APIs typically quote in RUB. Mark RUB so controller converts correctly.
                            'currency' => 'RUB',
                        ];
                    }
                }
                return $services;
            }
        }
        Log::error('DaisySMS getServices failed', [ 'url' => $url, 'country' => $country, 'status' => $response->status(), 'body_sample' => substr($response->body(), 0, 300) ]);
        return [];
    }

    public function createOrder(SmsService $smsService, string $country, string $service): array
    {
        // DASSY only supports US (187) - reject all other countries
        $dassyCountryCode = $this->mapFrontendToDassyCountry($country);
        if ($dassyCountryCode !== '187') {
            throw new Exception("DASSY only supports US numbers, not {$country}");
        }
        
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getNumber&service=' . urlencode($service) . '&country=' . urlencode($dassyCountryCode);
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $body = trim($response->body());
            if (strpos($body, 'ACCESS_NUMBER:') === 0) {
                $parts = explode(':', $body, 3);
                if (count($parts) >= 3) {
                    return [
                        'order_id' => $parts[1],
                        'phone_number' => $parts[2],
                        'cost' => 0,
                        'status' => 'active',
                        'expires_at' => now()->addMinutes(15)
                    ];
                }
            }
            if ($body === 'NO_NUMBERS') throw new Exception('No numbers available for this service');
            if ($body === 'NO_MONEY') throw new Exception('Insufficient balance');
            if ($body === 'TOO_MANY_ACTIVE_RENTALS') throw new Exception('Too many active rentals');
        }
        Log::error('DaisySMS createOrder failed', [ 'url' => $url, 'status' => $response->status(), 'body' => $response->body() ]);
        throw new Exception('Failed to create DaisySMS order: ' . $response->body());
    }

    public function getSmsCode(SmsService $smsService, string $orderId): ?string
    {
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getStatus&id=' . urlencode($orderId);
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $body = trim($response->body());
            if (strpos($body, 'STATUS_OK:') === 0) {
                $parts = explode(':', $body, 2);
                if (count($parts) >= 2) return $parts[1];
            }
            if ($body === 'STATUS_WAIT_CODE') return null;
            // Surface cancellations/expirations as exceptions so controller can auto-cancel/refund
            if ($body === 'STATUS_CANCEL' || stripos($body, 'CANCEL') !== false || stripos($body, 'EXPIRED') !== false) {
                throw new Exception($body);
            }
        }
        return null;
    }

    public function cancelOrder(SmsService $smsService, string $orderId): bool
    {
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=setStatus&id=' . urlencode($orderId) . '&status=8';
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $body = trim($response->body());
            return $body === 'ACCESS_CANCEL';
        }
        return false;
    }

    public function getBalance(SmsService $smsService): float
    {
        $config = $smsService->getApiConfig();
        $url = $config['api_url'] . '?api_key=' . urlencode($config['api_key']) . '&action=getBalance';
        $response = $this->httpClient->get($url);
        if ($response->successful()) {
            $body = trim($response->body());
            if (strpos($body, 'ACCESS_BALANCE:') === 0) {
                $parts = explode(':', $body, 2);
                if (count($parts) >= 2) return (float)$parts[1];
            }
        }
        return 0.0;
    }

    /**
     * Map Daisy numeric country code to ISO2 and friendly name
     */
    private function mapCountry(string $daisyCode): array
    {
        // Minimal mapping; extend as needed. 187 is USA per Daisy/SMS-Activate.
        $map = [
            '187' => ['US', 'United States'],
            // Add more Daisy numeric codes here if needed
        ];
        if (isset($map[$daisyCode])) return $map[$daisyCode];
        return ['', 'Country ' . $daisyCode];
    }

    private function mapFrontendToDassyCountry(string $frontendCountry): string
    {
        // Map frontend country codes (us, uk, etc.) to DASSY numeric codes
        $map = [
            'us' => '187',  // United States
            'uk' => '44',   // United Kingdom (if available)
            'ca' => '1',    // Canada (if available)
            'au' => '61',   // Australia (if available)
            // Add more mappings as needed
        ];
        
        $lowerCountry = strtolower($frontendCountry);
        return $map[$lowerCountry] ?? $frontendCountry; // Return original if no mapping found
    }

    /**
     * Convert USD provider price to NGN using global config and markup
     * IMPORTANT: Enforces minimum price of ₦1500 for all SMS services
     */
    private function convertToNgn(float $usd, string $provider): float
    {
        // For Dassy: Simple rule - add 50% on USD then convert to NGN using FX
        if ($provider === 'dassy') {
            $fxDefault = (float) config('services.sms_fx.ngn_per_usd', 1600);
            $fxOverride = (float) config('services.sms_fx.providers.dassy', 0);
            $fx = $fxOverride > 0 ? $fxOverride : $fxDefault;
            $ngn = (float) ceil($usd * 1.5 * $fx);

            // Apply global clamps
            $min = (float) config('services.sms_price_limits.min_ngn', 1500);
            $max = (float) config('services.sms_price_limits.max_ngn', 3000000);
            if ($ngn < $min) { $ngn = $min; }
            if ($ngn > $max) { $ngn = $max; }
            return $ngn;
        }

        // Default logic for other providers (FX + markup + optional VAT + min)
        $fx = (float) (config('services.sms_fx.ngn_per_usd', 1600));
        $fxFloor = (float) (config('services.sms_fx.min_ngn_per_usd', 1200));
        if ($fx < $fxFloor) { $fx = $fxFloor; }

        $provFx = (float) (config("services.sms_fx.providers.{$provider}", 0));
        if ($provFx > 0) { $fx = max($provFx, $fxFloor); }

        $markup = (float) (config('services.sms_markup.percent', 10));
        $provMarkup = (float) (config("services.sms_markup.providers.{$provider}", -1));
        if ($provMarkup >= 0) { $markup = $provMarkup; }

        $ngn = $usd * $fx;
        if ($markup > 0) { $ngn *= (1 + $markup / 100); }

        // Fixed VAT/add-on from settings table (sms_vat), default NGN 700
        try {
            $vat = (float) (DB::table('settings')->where('key', 'sms_vat')->value('value') ?? 700);
            if ($vat > 0) { $ngn += $vat; }
        } catch (\Throwable $e) {
            $ngn += 700; // fallback if settings table unavailable
        }

        // Round up to nearest 1 NGN
        $ngn = (float) ceil($ngn);

        // Enforce min/max clamps
        $min = (float) config('services.sms_price_limits.min_ngn', 1500);
        $max = (float) config('services.sms_price_limits.max_ngn', 3000000);
        if ($ngn < $min) { $ngn = $min; }
        if ($ngn > $max) { $ngn = $max; }

        return $ngn;
    }
}
