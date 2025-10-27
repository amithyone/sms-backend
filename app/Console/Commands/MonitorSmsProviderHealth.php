<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SmsService;
use App\Models\SmsOrder;
use App\Services\SmsProviderService;

class MonitorSmsProviderHealth extends Command
{
    protected $signature = 'sms:monitor-provider-health';
    protected $description = 'Monitor SMS provider health and performance metrics';

    public function handle()
    {
        $this->info('🏥 Monitoring SMS provider health...');
        
        $providers = ['5sim', 'smspool', 'dassy', 'tiger_sms'];
        $healthReport = [];
        
        foreach ($providers as $providerName) {
            $health = $this->checkProviderHealth($providerName);
            $healthReport[$providerName] = $health;
            
            $status = $health['status'];
            $statusIcon = $status === 'healthy' ? '✅' : ($status === 'warning' ? '⚠️' : '❌');
            
            $this->info("{$statusIcon} {$providerName}: {$health['status']} (Success: {$health['success_rate']}%)");
            
            if ($health['issues']) {
                foreach ($health['issues'] as $issue) {
                    $this->warn("  - {$issue}");
                }
            }
        }
        
        // Log health report
        Log::info('SMS Provider Health Report', $healthReport);
        
        // Auto-disable unhealthy providers
        $this->autoManageProviders($healthReport);
        
        $this->info('✅ Provider health monitoring completed.');
    }
    
    private function checkProviderHealth(string $providerName): array
    {
        $smsService = SmsService::where('provider', $providerName)->first();
        
        if (!$smsService) {
            return [
                'status' => 'error',
                'success_rate' => 0,
                'issues' => ['Provider not configured'],
                'recommendations' => ['Configure provider in database']
            ];
        }
        
        $issues = [];
        $recommendations = [];
        
        // Check balance
        $balance = $smsService->balance ?? 0;
        if ($balance < 10) {
            $issues[] = 'Low balance: $' . number_format($balance, 2);
            $recommendations[] = 'Top up provider balance';
        }
        
        // Check recent success rate (last 24 hours)
        $recentOrders = SmsOrder::join('sms_services', 'sms_orders.sms_service_id', '=', 'sms_services.id')
            ->where('sms_orders.provider_order_id', '!=', null)
            ->where('sms_orders.created_at', '>', now()->subDay())
            ->where('sms_services.provider', $providerName)
            ->select('sms_orders.*')
            ->get();
            
        if ($recentOrders->count() > 0) {
            $successfulOrders = $recentOrders->where('status', 'completed')->count();
            $successRate = ($successfulOrders / $recentOrders->count()) * 100;
            
            if ($successRate < 50) {
                $issues[] = "Low success rate: {$successRate}% (last 24h)";
                $recommendations[] = 'Check provider API status';
            }
            
            if ($successRate < 30) {
                $issues[] = "Critical success rate: {$successRate}%";
                $recommendations[] = 'Consider disabling provider';
            }
        } else {
            $successRate = 100; // No recent orders, assume healthy
        }
        
        // Check response time (if we have API access)
        $responseTime = $this->checkProviderResponseTime($smsService);
        if ($responseTime > 10) {
            $issues[] = "Slow response time: {$responseTime}s";
            $recommendations[] = 'Provider may be overloaded';
        }
        
        // Determine overall status
        $status = 'healthy';
        if (!empty($issues)) {
            $criticalIssues = array_filter($issues, function($issue) {
                return strpos($issue, 'Critical') !== false || strpos($issue, 'not configured') !== false;
            });
            $status = !empty($criticalIssues) ? 'critical' : 'warning';
        }
        
        return [
            'status' => $status,
            'success_rate' => round($successRate, 1),
            'balance' => $balance,
            'response_time' => $responseTime,
            'recent_orders' => $recentOrders->count(),
            'issues' => $issues,
            'recommendations' => $recommendations
        ];
    }
    
    private function checkProviderResponseTime(SmsService $smsService): float
    {
        try {
            $startTime = microtime(true);
            $smsProviderService = app(SmsProviderService::class);
            
            // Try to get balance (lightweight operation)
            $smsProviderService->getBalance($smsService);
            
            $endTime = microtime(true);
            return round($endTime - $startTime, 2);
            
        } catch (\Exception $e) {
            return 999; // Indicate API failure
        }
    }
    
    private function autoManageProviders(array $healthReport): void
    {
        foreach ($healthReport as $providerName => $health) {
            if ($health['status'] === 'critical') {
                $this->warn("🚨 Auto-disabling critical provider: {$providerName}");
                
                SmsService::where('provider', $providerName)
                    ->update(['is_active' => false]);
                    
                Log::warning('Provider auto-disabled due to critical health issues', [
                    'provider' => $providerName,
                    'health' => $health
                ]);
            }
        }
    }
}
