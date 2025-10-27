<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VtuNgService;

class CheckVtuBalance extends Command
{
    protected $signature = 'vtu:check-balance';
    protected $description = 'Check VTU.ng balance';

    public function handle()
    {
        $this->info('💰 Checking VTU.ng balance...');
        $this->newLine();

        try {
            $vtuService = new VtuNgService();
            $result = $vtuService->getBalance();

            if ($result['success']) {
                $balance = $result['balance'];
                $currency = $result['currency'];
                $this->info("✅ VTU.ng Balance: {$currency} " . number_format($balance, 2));
            } else {
                $message = $result['message'] ?? 'Unknown error';
                $this->error('❌ Failed to get VTU balance: ' . $message);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error checking VTU balance: ' . $e->getMessage());
        }
    }
}
