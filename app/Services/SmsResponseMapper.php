<?php

namespace App\Services;

use Illuminate\Support\Arr;

class SmsResponseMapper
{
    /**
     * Normalize service price list responses from different providers into a common shape.
     * Expected output: [ [ 'service' => string, 'name' => string, 'cost' => float, 'count' => int ] ]
     */
    public static function mapServices(string $provider, mixed $raw, ?string $countryId = null): array
    {
        switch ($provider) {
            case '5sim':
                // 5sim handler getNumbersStatus: { serviceCode_countryId: count }
                // or getPrices: country => { service => { cost, count } }
                if (is_array($raw) && $countryId && isset($raw[$countryId]) && is_array($raw[$countryId])) {
                    $rows = [];
                    foreach ($raw[$countryId] as $serviceCode => $data) {
                        $rows[] = [
                            'service' => (string) $serviceCode,
                            'name' => self::serviceName($serviceCode),
                            'cost' => (float) ($data['cost'] ?? 0),
                            'count' => (int) ($data['count'] ?? 0),
                        ];
                    }
                    return $rows;
                }
                return [];

            case 'dassy': // DaisySMS
                // getPrices returns: countryId => serviceCode => { cost, count }
                if (is_array($raw) && $countryId && isset($raw[$countryId]) && is_array($raw[$countryId])) {
                    $rows = [];
                    foreach ($raw[$countryId] as $serviceCode => $data) {
                        $rows[] = [
                            'service' => (string) $serviceCode,
                            'name' => self::serviceName($serviceCode),
                            'cost' => (float) ($data['cost'] ?? 0),
                            'count' => (int) ($data['count'] ?? 0),
                        ];
                    }
                    return $rows;
                }
                return [];

            case 'tiger_sms':
                // Tiger getPrices: similar to Daisy/5sim country=>service=>{cost,count}
                if (is_array($raw) && $countryId && isset($raw[$countryId]) && is_array($raw[$countryId])) {
                    $rows = [];
                    foreach ($raw[$countryId] as $serviceCode => $data) {
                        $rows[] = [
                            'service' => (string) $serviceCode,
                            'name' => self::serviceName($serviceCode),
                            'cost' => (float) ($data['cost'] ?? 0),
                            'count' => (int) ($data['count'] ?? 0),
                        ];
                    }
                    return $rows;
                }
                return [];

            case 'textverified':
                // TextVerified services is a flat array of { serviceName, capability }
                if (is_array($raw)) {
                    $rows = [];
                    foreach ($raw as $svc) {
                        $code = (string) ($svc['serviceName'] ?? '');
                        if ($code === '') { continue; }
                        $rows[] = [
                            'service' => $code,
                            'name' => $code,
                            'cost' => 0.0,
                            'count' => 0,
                        ];
                    }
                    return $rows;
                }
                return [];

            default:
                return [];
        }
    }

    /**
     * Minimal mapping of country name by common IDs. Fallback to the same code string.
     */
    public static function countryName(string $countryId): string
    {
        $map = [ '187' => 'United States', 'US' => 'United States' ];
        return $map[$countryId] ?? $countryId;
    }

    /**
     * Lightweight service name resolver; backend should not rely on completeness here.
     */
    public static function serviceName(string $serviceCode): string
    {
        $map = [
            'wa' => 'WhatsApp',
            'tg' => 'Telegram',
            'fb' => 'Facebook',
            'ig' => 'Instagram',
            'go' => 'Google',
            'ds' => 'Discord',
            'sn' => 'Snapchat',
        ];
        return $map[$serviceCode] ?? ucfirst($serviceCode);
    }
}


