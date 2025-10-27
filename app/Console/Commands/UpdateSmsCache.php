<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsCacheService;

class UpdateSmsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:update-cache {--force : Force update even if recently updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cached SMS services from all providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting SMS cache update...');
        
        $startTime = now();
        $smsCacheService = new SmsCacheService();
        
        try {
            $result = $smsCacheService->updateAllServices();
            
            $duration = $startTime->diffInSeconds(now());
            
            $this->info("✅ Cache update completed successfully!");
            $this->info("📊 Updated: {$result['updated']} services");
            $this->info("❌ Errors: {$result['errors']} providers");
            $this->info("⏱️ Duration: {$duration} seconds");
            
            // Show statistics
            $stats = $smsCacheService->getCacheStats();
            $this->info("📈 Cache Statistics:");
            $this->info("   Total Services: {$stats['total_services']}");
            $this->info("   Active Services: {$stats['active_services']}");
            $this->info("   Last Updated: {$stats['last_updated']}");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Cache update failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}