<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup {--days=30 : Number of days to keep logs} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old log files to prevent disk space issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $logPath = storage_path('logs');
        
        $this->info("🧹 Starting log cleanup (keeping logs for {$days} days)");
        
        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No files will be deleted");
        }

        $deletedCount = 0;
        $deletedSize = 0;

        // Get all log files
        $logFiles = File::glob($logPath . '/*.log');
        $dailyLogFiles = File::glob($logPath . '/*-*.log');

        $allFiles = array_merge($logFiles, $dailyLogFiles);
        $cutoffTime = now()->subDays($days)->timestamp;

        foreach ($allFiles as $file) {
            $fileTime = filemtime($file);
            $fileSize = filesize($file);
            
            if ($fileTime < $cutoffTime) {
                $deletedCount++;
                $deletedSize += $fileSize;
                
                if ($dryRun) {
                    $this->line("Would delete: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
                } else {
                    if (File::delete($file)) {
                        $this->line("Deleted: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
                    } else {
                        $this->error("Failed to delete: " . basename($file));
                    }
                }
            }
        }

        // Clean up backup directory
        $backupPath = $logPath . '/backups';
        if (File::exists($backupPath)) {
            $backupFiles = File::glob($backupPath . '/*.log.gz');
            
            foreach ($backupFiles as $file) {
                $fileTime = filemtime($file);
                $fileSize = filesize($file);
                
                if ($fileTime < $cutoffTime) {
                    $deletedCount++;
                    $deletedSize += $fileSize;
                    
                    if ($dryRun) {
                        $this->line("Would delete backup: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
                    } else {
                        if (File::delete($file)) {
                            $this->line("Deleted backup: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
                        } else {
                            $this->error("Failed to delete backup: " . basename($file));
                        }
                    }
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("✅ Cleanup completed!");
            $this->info("📊 Files processed: {$deletedCount}");
            $this->info("💾 Space freed: " . $this->formatBytes($deletedSize));
        } else {
            $this->info("✅ No old log files found to clean up");
        }

        // Show current log directory size
        $currentSize = $this->getDirectorySize($logPath);
        $this->info("📁 Current log directory size: " . $this->formatBytes($currentSize));

        return Command::SUCCESS;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get directory size recursively
     */
    private function getDirectorySize($directory)
    {
        $size = 0;
        
        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }
}
