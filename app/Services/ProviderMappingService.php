<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderMappingService
{
    /**
     * Map standard country code to provider-specific country code
     */
    public function mapCountryCode(string $standardCode, string $provider): string
    {
        $mappings = $this->getCountryMappings();
        
        if (isset($mappings[$provider][$standardCode])) {
            return $mappings[$provider][$standardCode];
        }
        
        // Default fallback - return the original code
        return $standardCode;
    }
    
    /**
     * Map standard service code to provider-specific service code
     */
    public function mapServiceCode(string $standardCode, string $provider): string
    {
        $mappings = $this->getServiceMappings();
        
        if (isset($mappings[$provider][$standardCode])) {
            return $mappings[$provider][$standardCode];
        }
        
        // Default fallback - return the original code
        return $standardCode;
    }
    
    /**
     * Get country code mappings for all providers
     */
    private function getCountryMappings(): array
    {
        return [
            'tiger_sms' => [
                'US' => '187',
                'GB' => '2',
                'CA' => '38',
                'AU' => '61',
                'DE' => '49',
                'FR' => '33',
                'IT' => '39',
                'ES' => '34',
                'NL' => '31',
                'SE' => '46',
                'NO' => '47',
                'DK' => '45',
                'FI' => '358',
                'PL' => '48',
                'CZ' => '420',
                'HU' => '36',
                'AT' => '43',
                'CH' => '41',
                'BE' => '32',
                'IE' => '353',
                'PT' => '351',
                'GR' => '30',
                'IN' => '22',
                'BR' => '55',
                'MX' => '52',
                'JP' => '81',
                'KR' => '82',
                'CN' => '86',
                'RU' => '7',
                'ZA' => '27',
                'NG' => '234',
                'KE' => '254',
            ],
            'dassy' => [
                'US' => '187',
                'GB' => '2',
                'CA' => '38',
                'AU' => '61',
                'DE' => '49',
                'FR' => '33',
                'IT' => '39',
                'ES' => '34',
                'NL' => '31',
                'SE' => '46',
                'NO' => '47',
                'DK' => '45',
                'FI' => '358',
                'PL' => '48',
                'CZ' => '420',
                'HU' => '36',
                'AT' => '43',
                'CH' => '41',
                'BE' => '32',
                'IE' => '353',
                'PT' => '351',
                'GR' => '30',
                'IN' => '22',
                'BR' => '55',
                'MX' => '52',
                'JP' => '81',
                'KR' => '82',
                'CN' => '86',
                'RU' => '7',
                'ZA' => '27',
                'NG' => '234',
                'KE' => '254',
            ],
            '5sim' => [
                // 5SIM uses ISO2 codes directly
                'US' => 'US',
                'GB' => 'GB',
                'CA' => 'CA',
                'AU' => 'AU',
                'DE' => 'DE',
                'FR' => 'FR',
                'IT' => 'IT',
                'ES' => 'ES',
                'NL' => 'NL',
                'SE' => 'SE',
                'NO' => 'NO',
                'DK' => 'DK',
                'FI' => 'FI',
                'PL' => 'PL',
                'CZ' => 'CZ',
                'HU' => 'HU',
                'AT' => 'AT',
                'CH' => 'CH',
                'BE' => 'BE',
                'IE' => 'IE',
                'PT' => 'PT',
                'GR' => 'GR',
                'IN' => 'IN',
                'BR' => 'BR',
                'MX' => 'MX',
                'JP' => 'JP',
                'KR' => 'KR',
                'CN' => 'CN',
                'RU' => 'RU',
                'ZA' => 'ZA',
                'NG' => 'NG',
                'KE' => 'KE',
            ],
            'smspool' => [
                // SMSPool uses ISO2 codes directly
                'US' => 'US',
                'GB' => 'GB',
                'CA' => 'CA',
                'AU' => 'AU',
                'DE' => 'DE',
                'FR' => 'FR',
                'IT' => 'IT',
                'ES' => 'ES',
                'NL' => 'NL',
                'SE' => 'SE',
                'NO' => 'NO',
                'DK' => 'DK',
                'FI' => 'FI',
                'PL' => 'PL',
                'CZ' => 'CZ',
                'HU' => 'HU',
                'AT' => 'AT',
                'CH' => 'CH',
                'BE' => 'BE',
                'IE' => 'IE',
                'PT' => 'PT',
                'GR' => 'GR',
                'IN' => 'IN',
                'BR' => 'BR',
                'MX' => 'MX',
                'JP' => 'JP',
                'KR' => 'KR',
                'CN' => 'CN',
                'RU' => 'RU',
                'ZA' => 'ZA',
                'NG' => 'NG',
                'KE' => 'KE',
            ],
            'textverified' => [
                // TextVerified only supports US
                'US' => 'US',
            ],
        ];
    }
    
    /**
     * Get service code mappings for all providers
     */
    private function getServiceMappings(): array
    {
        return [
            'tiger_sms' => [
                'whatsapp' => 'wa',
                'telegram' => 'tg',
                'signal' => 'bw',
                'tinder' => 'oi',
                'google' => 'go',
                'facebook' => 'fb',
                'instagram' => 'ig',
                'twitter' => 'tw',
                'tiktok' => 'tn',
                'discord' => 'ds',
                'snapchat' => 'sn',
                'amazon' => 'am',
                'microsoft' => 'mc',
                'apple' => 'ap',
                'uber' => 'ub',
                'lyft' => 'ly',
                'netflix' => 'nf',
                'spotify' => 'sp',
                'youtube' => 'yt',
                'linkedin' => 'li',
                'pinterest' => 'pt',
                'reddit' => 're',
                'twitch' => 'tv',
                'viber' => 'vi',
                'wechat' => 'wc',
                'line' => 'ln',
                'kakao' => 'kt',
                'zalo' => 'za',
                'vk' => 'vk',
                'odnoklassniki' => 'ok',
                'mailru' => 'ma',
                'yandex' => 'ya',
            ],
            'dassy' => [
                'whatsapp' => 'wa',
                'telegram' => 'tg',
                'signal' => 'bw',
                'tinder' => 'oi',
                'google' => 'go',
                'facebook' => 'fb',
                'instagram' => 'ig',
                'twitter' => 'tw',
                'tiktok' => 'tn',
                'discord' => 'ds',
                'snapchat' => 'sn',
                'amazon' => 'am',
                'microsoft' => 'mc',
                'apple' => 'ap',
                'uber' => 'ub',
                'lyft' => 'ly',
                'netflix' => 'nf',
                'spotify' => 'sp',
                'youtube' => 'yt',
                'linkedin' => 'li',
                'pinterest' => 'pt',
                'reddit' => 're',
                'twitch' => 'tv',
                'viber' => 'vi',
                'wechat' => 'wc',
                'line' => 'ln',
                'kakao' => 'kt',
                'zalo' => 'za',
                'vk' => 'vk',
                'odnoklassniki' => 'ok',
                'mailru' => 'ma',
                'yandex' => 'ya',
            ],
            '5sim' => [
                // 5SIM uses standard service names
                'whatsapp' => 'whatsapp',
                'telegram' => 'telegram',
                'signal' => 'signal',
                'tinder' => 'tinder',
                'google' => 'google',
                'facebook' => 'facebook',
                'instagram' => 'instagram',
                'twitter' => 'twitter',
                'tiktok' => 'tiktok',
                'discord' => 'discord',
                'snapchat' => 'snapchat',
                'amazon' => 'amazon',
                'microsoft' => 'microsoft',
                'apple' => 'apple',
                'uber' => 'uber',
                'lyft' => 'lyft',
                'netflix' => 'netflix',
                'spotify' => 'spotify',
                'youtube' => 'youtube',
                'linkedin' => 'linkedin',
                'pinterest' => 'pinterest',
                'reddit' => 'reddit',
                'twitch' => 'twitch',
                'viber' => 'viber',
                'wechat' => 'wechat',
                'line' => 'line',
                'kakao' => 'kakao',
                'zalo' => 'zalo',
                'vk' => 'vk',
                'odnoklassniki' => 'odnoklassniki',
                'mailru' => 'mailru',
                'yandex' => 'yandex',
            ],
            'smspool' => [
                // SMSPool uses standard service names
                'whatsapp' => 'whatsapp',
                'telegram' => 'telegram',
                'signal' => 'signal',
                'tinder' => 'tinder',
                'google' => 'google',
                'facebook' => 'facebook',
                'instagram' => 'instagram',
                'twitter' => 'twitter',
                'tiktok' => 'tiktok',
                'discord' => 'discord',
                'snapchat' => 'snapchat',
                'amazon' => 'amazon',
                'microsoft' => 'microsoft',
                'apple' => 'apple',
                'uber' => 'uber',
                'lyft' => 'lyft',
                'netflix' => 'netflix',
                'spotify' => 'spotify',
                'youtube' => 'youtube',
                'linkedin' => 'linkedin',
                'pinterest' => 'pinterest',
                'reddit' => 'reddit',
                'twitch' => 'twitch',
                'viber' => 'viber',
                'wechat' => 'wechat',
                'line' => 'line',
                'kakao' => 'kakao',
                'zalo' => 'zalo',
                'vk' => 'vk',
                'odnoklassniki' => 'odnoklassniki',
                'mailru' => 'mailru',
                'yandex' => 'yandex',
            ],
            'textverified' => [
                // TextVerified uses standard service names
                'whatsapp' => 'whatsapp',
                'telegram' => 'telegram',
                'signal' => 'signal',
                'tinder' => 'tinder',
                'google' => 'google',
                'facebook' => 'facebook',
                'instagram' => 'instagram',
                'twitter' => 'twitter',
                'tiktok' => 'tiktok',
                'discord' => 'discord',
                'snapchat' => 'snapchat',
                'amazon' => 'amazon',
                'microsoft' => 'microsoft',
                'apple' => 'apple',
                'uber' => 'uber',
                'lyft' => 'lyft',
                'netflix' => 'netflix',
                'spotify' => 'spotify',
                'youtube' => 'youtube',
                'linkedin' => 'linkedin',
                'pinterest' => 'pinterest',
                'reddit' => 'reddit',
                'twitch' => 'twitch',
                'viber' => 'viber',
                'wechat' => 'wechat',
                'line' => 'line',
                'kakao' => 'kakao',
                'zalo' => 'zalo',
                'vk' => 'vk',
                'odnoklassniki' => 'odnoklassniki',
                'mailru' => 'mailru',
                'yandex' => 'yandex',
            ],
        ];
    }
    
    /**
     * Map both country and service codes for a provider
     */
    public function mapCodes(string $countryCode, string $serviceCode, string $provider): array
    {
        return [
            'country' => $this->mapCountryCode($countryCode, $provider),
            'service' => $this->mapServiceCode($serviceCode, $provider)
        ];
    }
    
    /**
     * Check if a provider supports a specific country
     */
    public function supportsCountry(string $countryCode, string $provider): bool
    {
        $mappings = $this->getCountryMappings();
        return isset($mappings[$provider][$countryCode]);
    }
    
    /**
     * Check if a provider supports a specific service
     */
    public function supportsService(string $serviceCode, string $provider): bool
    {
        $mappings = $this->getServiceMappings();
        return isset($mappings[$provider][$serviceCode]);
    }
    
    /**
     * Get all supported countries for a provider
     */
    public function getSupportedCountries(string $provider): array
    {
        $mappings = $this->getCountryMappings();
        return array_keys($mappings[$provider] ?? []);
    }
    
    /**
     * Get all supported services for a provider
     */
    public function getSupportedServices(string $provider): array
    {
        $mappings = $this->getServiceMappings();
        return array_keys($mappings[$provider] ?? []);
    }
}
