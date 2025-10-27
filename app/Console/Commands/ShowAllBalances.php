<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VtuNgService;

class ShowAllBalances extends Command
{
    protected $signature = 'balances:show-all';
    protected $description = 'Show all provider balances (SMS + VTU)';

    public function handle()
    {
        $this->info('💰 ALL PROVIDER BALANCES SUMMARY');
        $this->newLine();
        $this->line('═' . str_repeat('═', 50));

        // SMS Provider Balances
        $this->info('📱 SMS PROVIDER BALANCES:');
        $this->newLine();

        $providers = [
            '5sim' => \App\Services\Sms\Providers\FiveSimProvider::class,
            'dassy' => \App\Services\Sms\Providers\DassyProvider::class,
            'smspool' => \App\Services\Sms\Providers\SmsPoolProvider::class,
            'tiger_sms' => \App\Services\Sms\Providers\TigerSmsProvider::class,
            'textverified' => \App\Services\Sms\Providers\TextVerifiedProvider::class,
        ];

        $totalSmsBalance = 0;

        foreach ($providers as $providerName => $providerClass) {
            try {
                $smsService = \App\Models\SmsService::where('provider', $providerName)->first();
                if (!$smsService) {
                    $this->warn("⚠️  {$providerName}: No SMS service configuration found");
                    continue;
                }

                $provider = new $providerClass();
                $balance = $provider->getBalance($smsService);
                
                $currency = $this->getCurrency($providerName);
                $formattedBalance = $this->formatBalance($balance, $currency);
                
                $this->line("✅ {$providerName}: {$formattedBalance}");
                $totalSmsBalance += $balance;
                
            } catch (\Exception $e) {
                $this->error("❌ {$providerName}: Error - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->line("💵 Total SMS Balance: $" . number_format($totalSmsBalance, 2));
        $this->newLine();

        // VTU Balance
        $this->info('🔌 VTU PROVIDER BALANCES:');
        $this->newLine();

        try {
            $vtuService = new VtuNgService();
            $result = $vtuService->getBalance();

            if ($result['success']) {
                $balance = $result['balance'];
                $currency = $result['currency'];
                $this->line("✅ VTU.ng: {$currency} " . number_format($balance, 2));
            } else {
                $message = $result['message'] ?? 'Unknown error';
                $this->error('❌ VTU.ng: Failed - ' . $message);
            }

        } catch (\Exception $e) {
            $this->error('❌ VTU.ng: Error - ' . $e->getMessage());
        }

        $this->newLine();
        $this->line('═' . str_repeat('═', 50));
        $this->info('📊 SUMMARY:');
        $this->line("• SMS Providers: $" . number_format($totalSmsBalance, 2));
        $this->line("• VTU Provider: NGN " . number_format($result['balance'] ?? 0, 2));
        $this->newLine();
        $this->info('✅ Balance check completed!');
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
