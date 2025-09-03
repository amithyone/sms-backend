<?php

namespace App\Services;

class VtuProviderManager
{
    public function __construct(
        private VtuNgService $vtuNg,
        private IrechargeService $irecharge
    ) {}

    public function getAirtimeNetworks(): array
    {
        $r = $this->vtuNg->getAirtimeNetworks();
        return !empty($r) ? $r : $this->irecharge->getAirtimeNetworks();
    }

    public function getDataNetworks(): array
    {
        $r = $this->vtuNg->getDataNetworks();
        return !empty($r) ? $r : $this->irecharge->getDataNetworks();
    }

    public function getDataBundles(string $network): array
    {
        $r = $this->vtuNg->getDataBundles($network);
        return !empty($r) ? $r : $this->irecharge->getDataBundles($network);
    }

    public function purchaseAirtime(string $network, string $phone, float $amount, string $reference): array
    {
        try {
            return $this->vtuNg->purchaseAirtime($network, $phone, $amount, $reference);
        } catch (\Throwable $e) {
            return $this->irecharge->purchaseAirtime($network, $phone, $amount, $reference);
        }
    }

    public function purchaseDataBundle(string $network, string $phone, string $plan, string $reference): array
    {
        try {
            return $this->vtuNg->purchaseDataBundle($network, $phone, $plan, $reference);
        } catch (\Throwable $e) {
            return $this->irecharge->purchaseDataBundle($network, $phone, $plan, $reference);
        }
    }

    public function getTransactionStatus(string $reference): array
    {
        try {
            return $this->vtuNg->getTransactionStatus($reference);
        } catch (\Throwable $e) {
            return $this->irecharge->getTransactionStatus($reference);
        }
    }

    public function getBalance(): array
    {
        $r = $this->vtuNg->getBalance();
        if (!empty($r['success'])) { return $r; }
        return $this->irecharge->getBalance();
    }

    public function validatePhoneNumber(string $phone, string $network): bool
    {
        $r = $this->vtuNg->validatePhoneNumber($phone, $network);
        return $r ?: $this->irecharge->validatePhoneNumber($phone, $network);
    }
}
