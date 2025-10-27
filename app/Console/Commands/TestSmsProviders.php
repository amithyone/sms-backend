<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsProviderService;
use App\Models\SmsService;
use Illuminate\Support\Facades\Log;

class TestSmsProviders extends Command
{
    protected $signature = 'sms:test-providers {--provider= : Specific provider to test}';
    protected $description = 'Test SMS provider endpoints to verify they can receive codes';

    private $smsProviderService;

    public function __construct(SmsProviderService $smsProviderService)
    {
        parent::__construct();
        $this->smsProviderService = $smsProviderService;
    }

    public function handle()
    {
        $this->info('🧪 Testing SMS Provider Endpoints...');
        $this->info('════════════════════════════════════════════════');
        $this->newLine();
        
        $specificProvider = $this->option('provider');
        
        // Get all active SMS services
        $query = SmsService::where('is_active', true);
        if ($specificProvider) {
            $query->where('provider', $specificProvider);
        }
        $services = $query->get();
        
        if ($services->isEmpty()) {
            $this->error('No active SMS services found.');
            return 1;
        }
        
        $results = [];
        
        foreach ($services as $service) {
            $this->info("Testing: {$service->name} ({$service->provider})");
            $this->line('─────────────────────────────────────────────');
            
            $testResult = [
                'provider' => $service->provider,
                'name' => $service->name,
                'api_configured' => false,
                'can_get_balance' => false,
                'balance' => null,
                'can_get_countries' => false,
                'countries_count' => 0,
                'can_get_services' => false,
                'services_count' => 0,
                'can_create_order' => 'not_tested',
                'can_get_sms_code' => 'not_tested',
                'error' => null
            ];
            
            try {
                // Test 1: Check if API is configured
                $config = $service->getApiConfig();
                if (!empty($config['api_key'])) {
                    $testResult['api_configured'] = true;
                    $this->line("  ✓ API Key configured");
                } else {
                    $testResult['error'] = 'No API key configured';
                    $this->error("  ✗ No API key configured");
                    $results[] = $testResult;
                    $this->newLine();
                    continue;
                }
                
                // Test 2: Get Balance
                try {
                    $balance = $this->smsProviderService->getBalance($service);
                    $testResult['can_get_balance'] = true;
                    $testResult['balance'] = $balance;
                    $this->line("  ✓ Balance: ₦{$balance}");
                } catch (\Exception $e) {
                    $this->warn("  ⚠ Balance check failed: {$e->getMessage()}");
                    $testResult['error'] = $e->getMessage();
                }
                
                // Test 3: Get Countries
                try {
                    $countries = $this->smsProviderService->getCountries($service);
                    $testResult['can_get_countries'] = true;
                    $testResult['countries_count'] = count($countries);
                    $this->line("  ✓ Countries: " . count($countries) . " available");
                } catch (\Exception $e) {
                    $this->warn("  ⚠ Countries check failed: {$e->getMessage()}");
                }
                
                // Test 4: Get Services (for Nigeria as example)
                try {
                    $servicesData = $this->smsProviderService->getServices($service, 'NG');
                    $testResult['can_get_services'] = true;
                    $testResult['services_count'] = count($servicesData);
                    $this->line("  ✓ Services (Nigeria): " . count($servicesData) . " available");
                } catch (\Exception $e) {
                    $this->warn("  ⚠ Services check failed: {$e->getMessage()}");
                }
                
                // Test 5: Test Order Creation (DRY RUN - don't actually create)
                $this->line("  ℹ Order creation: Would need actual purchase (skipped)");
                $testResult['can_create_order'] = 'skipped';
                
                // Test 6: Test SMS Code Retrieval (need existing order)
                $recentOrder = \App\Models\SmsOrder::where('sms_service_id', $service->id)
                    ->where('status', 'active')
                    ->latest()
                    ->first();
                
                if ($recentOrder) {
                    $this->line("  ℹ Testing SMS code retrieval on order: {$recentOrder->order_id}");
                    try {
                        $smsCode = $this->smsProviderService->getSmsCode($service, $recentOrder->provider_order_id);
                        if ($smsCode) {
                            $testResult['can_get_sms_code'] = 'success';
                            $this->line("  ✓ SMS Code Retrieved: {$smsCode}");
                        } else {
                            $testResult['can_get_sms_code'] = 'waiting';
                            $this->line("  ⏳ No SMS code yet (order still waiting)");
                        }
                    } catch (\Exception $e) {
                        $testResult['can_get_sms_code'] = 'failed';
                        $this->warn("  ⚠ SMS code check failed: {$e->getMessage()}");
                    }
                } else {
                    $this->line("  ℹ No active orders to test SMS retrieval");
                    $testResult['can_get_sms_code'] = 'no_orders';
                }
                
                $results[] = $testResult;
                
            } catch (\Exception $e) {
                $this->error("  ✗ Error testing provider: {$e->getMessage()}");
                $testResult['error'] = $e->getMessage();
                $results[] = $testResult;
            }
            
            $this->newLine();
        }
        
        // Summary Table
        $this->info('════════════════════════════════════════════════');
        $this->info('📊 TEST SUMMARY');
        $this->info('════════════════════════════════════════════════');
        $this->newLine();
        
        $this->table(
            ['Provider', 'API', 'Balance', 'Countries', 'Services', 'SMS Code'],
            array_map(function($r) {
                return [
                    $r['provider'],
                    $r['api_configured'] ? '✓' : '✗',
                    $r['can_get_balance'] ? '✓ ₦'.$r['balance'] : '✗',
                    $r['can_get_countries'] ? '✓ '.$r['countries_count'] : '✗',
                    $r['can_get_services'] ? '✓ '.$r['services_count'] : '✗',
                    $r['can_get_sms_code'] === 'success' ? '✓ Working' : 
                    ($r['can_get_sms_code'] === 'waiting' ? '⏳ No SMS' : 
                    ($r['can_get_sms_code'] === 'no_orders' ? 'ℹ No Orders' : 
                    ($r['can_get_sms_code'] === 'failed' ? '✗ Failed' : 'Not Tested')))
                ];
            }, $results)
        );
        
        $this->newLine();
        
        // Count successes
        $working = count(array_filter($results, fn($r) => 
            $r['api_configured'] && $r['can_get_balance'] && $r['can_get_countries']
        ));
        $total = count($results);
        
        if ($working === $total) {
            $this->info("✅ All {$total} providers working correctly!");
        } else {
            $this->warn("⚠ {$working}/{$total} providers working. Check errors above.");
        }
        
        $this->newLine();
        $this->info('💡 TIP: To test SMS code retrieval, create an active order first,');
        $this->info('   then run: php artisan sms:test-providers');
        
        return 0;
    }
}

