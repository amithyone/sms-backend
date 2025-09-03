<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseVtuService
{
    private array $providers = [];

    public function __construct()
    {
        $this->loadProviders();
    }

    private function loadProviders(): void
    {
        $this->providers = DB::table('vtu_services')
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get()
            ->toArray();
    }

    public function getAirtimeNetworks(): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'getAirtimeNetworks');
                if (!empty($result)) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for airtime networks: " . $e->getMessage());
                continue;
            }
        }
        
        // Return static fallback if all providers fail
        return $this->getStaticAirtimeNetworks();
    }

    public function getDataNetworks(): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'getDataNetworks');
                if (!empty($result)) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for data networks: " . $e->getMessage());
                continue;
            }
        }
        
        return $this->getStaticDataNetworks();
    }

    public function getDataBundles(string $network): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'getDataBundles', [$network]);
                if (!empty($result)) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for data bundles: " . $e->getMessage());
                continue;
            }
        }
        
        return $this->getStaticDataBundles($network);
    }

    public function purchaseAirtime(string $network, string $phone, float $amount, string $reference): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'purchaseAirtime', [$network, $phone, $amount, $reference]);
                if (!empty($result['success'])) {
                    $this->updateProviderStats($provider->id, true);
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for airtime purchase: " . $e->getMessage());
                $this->updateProviderStats($provider->id, false);
                continue;
            }
        }
        
        throw new Exception('All VTU providers failed for airtime purchase');
    }

    public function purchaseDataBundle(string $network, string $phone, string $plan, string $reference): array
    {
        $lastError = null;
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'purchaseDataBundle', [$network, $phone, $plan, $reference]);
                if (!empty($result['success'])) {
                    $this->updateProviderStats($provider->id, true);
                    return $result;
                }
                $lastError = $result['message'] ?? 'Unknown provider error';
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for data bundle purchase: " . $e->getMessage());
                $this->updateProviderStats($provider->id, false);
                $lastError = $e->getMessage();
                continue;
            }
        }
        
        throw new Exception('All VTU providers failed for data bundle purchase' . ($lastError ? (': ' . $lastError) : ''));
    }

    public function getTransactionStatus(string $reference): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'getTransactionStatus', [$reference]);
                if (!empty($result)) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for transaction status: " . $e->getMessage());
                continue;
            }
        }
        
        throw new Exception('All VTU providers failed for transaction status');
    }

    public function getBalance(): array
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'getBalance');
                if (!empty($result['success'])) {
                    // Update provider balance in database
                    $this->updateProviderBalance($provider->id, $result['balance'] ?? 0);
                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for balance check: " . $e->getMessage());
                continue;
            }
        }
        
        return ['success' => false, 'message' => 'All providers failed'];
    }

    public function validatePhoneNumber(string $phone, string $network): bool
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->callProviderMethod($provider, 'validatePhoneNumber', [$phone, $network]);
                if ($result) {
                    return true;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$provider->provider} failed for phone validation: " . $e->getMessage());
                continue;
            }
        }
        
        return false;
    }

    private function callProviderMethod($provider, string $method, array $args = []): mixed
    {
        switch ($provider->provider) {
            case 'vtu_ng':
                $service = new VtuNgService();
                break;
            case 'irecharge':
                $service = new IrechargeService();
                break;
            default:
                throw new Exception("Unknown provider: {$provider->provider}");
        }

        // Set provider configuration
        $service->setProviderConfig($provider);
        
        return call_user_func_array([$service, $method], $args);
    }

    private function updateProviderStats(int $providerId, bool $success): void
    {
        DB::table('vtu_services')
            ->where('id', $providerId)
            ->update([
                'total_orders' => DB::raw('total_orders + 1'),
                'successful_orders' => DB::raw('successful_orders + ' . ($success ? 1 : 0)),
                'success_rate' => DB::raw('(successful_orders + ' . ($success ? 1 : 0) . ') * 100.0 / (total_orders + 1)'),
                'updated_at' => now()
            ]);
    }

    private function updateProviderBalance(int $providerId, float $balance): void
    {
        DB::table('vtu_services')
            ->where('id', $providerId)
            ->update([
                'balance' => $balance,
                'last_balance_check' => now(),
                'updated_at' => now()
            ]);
    }

    private function getStaticAirtimeNetworks(): array
    {
        return [
            ['id' => 'mtn', 'name' => 'MTN', 'code' => 'MTN', 'status' => 'active'],
            ['id' => 'airtel', 'name' => 'Airtel', 'code' => 'AIRTEL', 'status' => 'active'],
            ['id' => 'glo', 'name' => 'Glo', 'code' => 'GLO', 'status' => 'active'],
            ['id' => '9mobile', 'name' => '9mobile', 'code' => '9MOBILE', 'status' => 'active']
        ];
    }

    private function getStaticDataNetworks(): array
    {
        return $this->getStaticAirtimeNetworks();
    }

    private function getStaticDataBundles(string $network): array
    {
        $networkKey = strtolower($network);
        $common = [
            ['plan' => '500MB', 'plan_name' => '500MB Daily', 'amount' => 150],
            ['plan' => '1GB', 'plan_name' => '1GB Daily', 'amount' => 300],
            ['plan' => '2GB', 'plan_name' => '2GB 2-Days', 'amount' => 500],
            ['plan' => '3GB', 'plan_name' => '3GB Weekly', 'amount' => 900],
            ['plan' => '5GB', 'plan_name' => '5GB Weekly', 'amount' => 1500],
            ['plan' => '10GB', 'plan_name' => '10GB Monthly', 'amount' => 3000],
        ];

        return array_map(function ($b) use ($networkKey) {
            return array_merge($b, ['network' => $networkKey]);
        }, $common);
    }
}
