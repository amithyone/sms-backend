<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredMeterCache extends Command
{
    protected $signature = 'meters:clean-expired';
    protected $description = 'Clean up expired verified meter cache entries';

    public function handle()
    {
        $this->info('Cleaning expired meter cache entries...');
        
        $deleted = DB::table('verified_meters')
            ->where('expires_at', '<', now())
            ->delete();
        
        if ($deleted > 0) {
            $this->info("✓ Deleted {$deleted} expired cache entries");
        } else {
            $this->info('No expired entries found');
        }
        
        // Also clean up entries older than 90 days regardless of expiration
        $oldDeleted = DB::table('verified_meters')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
        
        if ($oldDeleted > 0) {
            $this->info("✓ Deleted {$oldDeleted} old entries (90+ days)");
        }
        
        $remaining = DB::table('verified_meters')->count();
        $this->info("Remaining cached meters: {$remaining}");
        
        return 0;
    }
}

