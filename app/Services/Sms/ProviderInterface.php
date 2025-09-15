<?php

namespace App\Services\Sms;

use App\Models\SmsService;

interface ProviderInterface
{
    /**
     * Return available countries for this provider.
     * Normalized: [ { code, name } ]
     */
    public function getCountries(SmsService $smsService): array;

    /**
     * Return available services for a given country.
     * Normalized: [ { service, name, cost, count } ]
     */
    public function getServices(SmsService $smsService, string $country): array;

    /**
     * Create an order for country+service.
     * Returns: { order_id, phone_number, cost, status, expires_at }
     */
    public function createOrder(SmsService $smsService, string $country, string $service): array;

    /**
     * Retrieve SMS code (if any) for provider order id.
     */
    public function getSmsCode(SmsService $smsService, string $orderId): ?string;

    /**
     * Cancel an existing order.
     */
    public function cancelOrder(SmsService $smsService, string $orderId): bool;

    /**
     * Provider balance (if supported). Returns 0.0 if unsupported/unavailable.
     */
    public function getBalance(SmsService $smsService): float;
}
