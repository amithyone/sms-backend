<?php

namespace App\Services;

class ConfigurationService
{
    /**
     * Get SMS service configuration
     */
    public static function getSmsConfig(string $provider): array
    {
        return config("services.sms.{$provider}", []);
    }

    /**
     * Get VTU service configuration
     */
    public static function getVtuConfig(string $provider): array
    {
        return config("services.vtu.{$provider}", []);
    }

    /**
     * Get proxy service configuration
     */
    public static function getProxyConfig(string $provider): array
    {
        return config("services.proxy.{$provider}", []);
    }

    /**
     * Get payment service configuration
     */
    public static function getPaymentConfig(string $provider): array
    {
        return config("services.payment.{$provider}", []);
    }

    /**
     * Get CORS configuration
     */
    public static function getCorsConfig(): array
    {
        return config('services.cors', []);
    }

    /**
     * Get all service configurations
     */
    public static function getAllConfigs(): array
    {
        return [
            'sms' => config('services.sms'),
            'vtu' => config('services.vtu'),
            'proxy' => config('services.proxy'),
            'payment' => config('services.payment'),
            'cors' => config('services.cors'),
        ];
    }

    /**
     * Check if a service is configured
     */
    public static function isServiceConfigured(string $service, string $provider): bool
    {
        $config = config("services.{$service}.{$provider}");
        return !empty($config) && !empty($config['api_key'] ?? $config['username'] ?? null);
    }

    /**
     * Get service base URL
     */
    public static function getServiceBaseUrl(string $service, string $provider): ?string
    {
        return config("services.{$service}.{$provider}.base_url");
    }

    /**
     * Get service API key
     */
    public static function getServiceApiKey(string $service, string $provider): ?string
    {
        return config("services.{$service}.{$provider}.api_key");
    }
}
