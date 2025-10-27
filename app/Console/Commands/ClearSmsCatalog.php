<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ClearSmsCatalog extends Command
{
    protected $signature = 'sms:clear-catalog {--keep-cached=0 : Keep cached_sms_services table (1=yes)}';
    protected $description = 'Truncate SMS catalog tables (services and countries) to force a fresh rebuild';

    public function handle(): int
    {
        $keepCached = (string)$this->option('keep-cached') === '1';

        $this->info('⚠️ Truncating SMS catalog tables...');

        try {
            if (Schema::hasTable('sms_service_country_prices')) {
                DB::table('sms_service_country_prices')->truncate();
                $this->info(' - sms_service_country_prices truncated');
            } else {
                $this->line(' - sms_service_country_prices not found (skipped)');
            }

            if (Schema::hasTable('sms_country_catalog')) {
                DB::table('sms_country_catalog')->truncate();
                $this->info(' - sms_country_catalog truncated');
            } else {
                $this->line(' - sms_country_catalog not found (skipped)');
            }

            if (!$keepCached && Schema::hasTable('cached_sms_services')) {
                DB::table('cached_sms_services')->truncate();
                $this->info(' - cached_sms_services truncated');
            } elseif ($keepCached) {
                $this->line(' - cached_sms_services kept (per option)');
            } else {
                $this->line(' - cached_sms_services not found (skipped)');
            }

            $this->info('✅ Catalog tables cleared');
            Log::info('SMS catalog tables truncated', ['keep_cached' => $keepCached]);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Failed to truncate catalog: ' . $e->getMessage());
            Log::error('ClearSmsCatalog failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}


