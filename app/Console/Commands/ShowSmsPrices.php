<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowSmsPrices extends Command
{
    protected $signature = 'sms:show-prices {provider=dassy} {--country=} {--limit=50}';
    protected $description = 'Show cached SMS service prices for a provider (optionally filtered by country)';

    public function handle(): int
    {
        $provider = strtolower((string)$this->argument('provider'));
        $country = strtoupper((string)($this->option('country') ?? ''));
        $limit = (int)($this->option('limit') ?? 50);

        $q = DB::table('sms_service_country_prices')->where('provider', $provider);
        if ($country !== '') {
            $q->where('country_code', $country);
        }
        $rows = $q->orderBy('country_code')->orderBy('cost')->limit($limit)->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found for provider=' . $provider . ($country ? (', country=' . $country) : ''));
            return Command::SUCCESS;
        }

        // Build friendly name map if available
        $codes = $rows->pluck('service')->unique()->values()->all();
        $nameMap = DB::table('sms_service_codes')
            ->whereIn('code', $codes)
            ->where(function($q) use ($provider) {
                $q->where('provider', $provider)->orWhere('provider', 'all');
            })
            ->pluck('name', 'code');

        $this->info('Provider: ' . $provider . ($country ? (' | Country: ' . $country) : ''));
        $this->line(str_pad('Country', 10) . str_pad('Service', 14) . str_pad('Name', 28) . str_pad('Cost(NGN)', 14) . 'Count');
        foreach ($rows as $r) {
            $code = (string)$r->service;
            $name = (string)($nameMap[$code] ?? '');
            if ($name === '' && preg_match('/^[A-Z]{1,4}$/', strtoupper($code))) {
                // Basic prettify for very short codes
                $name = strtoupper($code);
            }
            $this->line(str_pad((string)$r->country_code, 10)
                . str_pad($code, 14)
                . str_pad(substr($name, 0, 26), 28)
                . str_pad(number_format((float)$r->cost), 14)
                . (string)$r->count);
        }

        return Command::SUCCESS;
    }
}


