<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsService;
use App\Services\Sms\Providers\FiveSimProvider;
use App\Services\Sms\Providers\DassyProvider;
use App\Services\Sms\Providers\SmsPoolProvider;
use App\Services\Sms\Providers\TigerSmsProvider;
use App\Services\Sms\Providers\TextVerifiedProvider;

class CheckAllBalances extends Command
{
    protected $signature = 'balances:check-all';
    protected $description = 'Check balances for all SMS providers';

    public function handle()
    {
        $this->info('💰 Checking all provider balances...');
        $this->newLine();

        $providers = [
            '5sim' => FiveSimProvider::class,
            'dassy' => DassyProvider::class,
            'smspool' => SmsPoolProvider::class,
            'tiger_sms' => TigerSmsProvider::class,
            'textverified' => TextVerifiedProvider::class,
        ];

        $totalBalance = 0;

        foreach ($providers as $providerName => $providerClass) {
            try {
                $smsService = SmsService::where('provider', $providerName)->first();
                if (!$smsService) {
                    $this->warn("⚠️  {$providerName}: No SMS service configuration found");
                    continue;
                }

                $provider = new $providerClass();
                $balance = $provider->getBalance($smsService);
                
                $currency = $this->getCurrency($providerName);
                $formattedBalance = $this->formatBalance($balance, $currency);
                
                $this->line("✅ {$providerName}: {$formattedBalance}");
                $totalBalance += $balance;
                
            } catch (\Exception $e) {
                $this->error("❌ {$providerName}: Error - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("💵 Total estimated balance: $" . number_format($totalBalance, 2));
    }

    private function getCurrency(string $provider): string
    {
        return match($provider) {
            '5sim' => 'USD',
            'dassy' => 'USD', 
            'smspool' => 'USD',
            'tiger_sms' => 'RUB',
            'textverified' => 'USD',
            default => 'USD'
        };
    }

    private function formatBalance(float $balance, string $currency): string
    {
        return $currency . ' ' . number_format($balance, 2);
    }
}
