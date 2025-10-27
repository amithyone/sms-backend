<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugDassyPrices extends Command
{
    protected $signature = 'sms:debug-dassy {--filter=} {--fx=1600} {--markup=50}';
    protected $description = 'Show raw Dassy getPrices USD and simple NGN = USD * (1+markup%) * fx';

    public function handle(): int
    {
        $baseUrl = config('services.sms.dassy.base_url');
        $apiKey = config('services.sms.dassy.api_key');
        if (!$baseUrl || !$apiKey) {
            $this->error('Dassy config missing.');
            return 1;
        }

        $url = $baseUrl . '?api_key=' . urlencode($apiKey) . '&action=getPrices';
        $this->line('GET ' . $url);
        $resp = Http::timeout(25)->get($url);
        if (!$resp->ok()) {
            $this->error('HTTP ' . $resp->status() . ': ' . substr($resp->body(), 0, 300));
            return 1;
        }
        $json = $resp->json();
        if (!is_array($json)) {
            $this->error('Unexpected response');
            return 1;
        }

        $filter = strtolower((string) $this->option('filter'));
        $fx = (float) $this->option('fx');
        $markup = (float) $this->option('markup');

        $this->info("fx={$fx}, markup={$markup}%  => NGN = USD * (1+markup/100) * fx");
        $count = 0;
        foreach ($json as $countryCode => $services) {
            if (!is_array($services)) continue;
            foreach ($services as $serviceCode => $serviceData) {
                if (!is_array($serviceData) || !isset($serviceData['cost'])) continue;
                $usd = (float) $serviceData['cost'];
                $qty = (int) ($serviceData['count'] ?? 0);
                $name = strtoupper((string) $serviceCode);
                if ($filter && strpos(strtolower($name), $filter) === false) continue;
                $ngn = ceil($usd * (1 + $markup / 100.0) * $fx);
                $this->line(sprintf('%-5s %-25s USD: %-8s  NGN(%.0f%%@%.0f): %-10s  Count: %d', (string)$countryCode, $name, number_format($usd, 2), $markup, $fx, number_format($ngn), $qty));
                $count++;
            }
        }
        if ($count === 0) {
            $this->warn('No matching services found. Try without --filter or different term.');
        }
        return 0;
    }
}


