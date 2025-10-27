<?php

namespace App\Console\Commands;

use App\Models\SmsOrder;
use App\Services\SmsProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollActiveSmsOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:poll-active-orders {--fast : Use fast polling for urgent orders} {--limit=50 : Maximum orders to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll active SMS orders to check for received SMS codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isFastMode = $this->option('fast');
        $limit = (int) $this->option('limit');
        
        $this->info('🔄 Polling active SMS orders for codes...' . ($isFastMode ? ' (FAST MODE)' : ''));
        
        // Get active orders with priority for popular services and recent orders
        $query = SmsOrder::with('smsService')
            ->where('status', 'active')
            ->where('expires_at', '>', now());
            
        if ($isFastMode) {
            // Fast mode: prioritize recent orders and popular services
            $query->where(function($q) {
                $q->where('created_at', '>', now()->subMinutes(10)) // Recent orders
                  ->orWhere('service', 'like', '%whatsapp%')
                  ->orWhere('service', 'like', '%wa%')
                  ->orWhere('service', 'like', '%telegram%')
                  ->orWhere('service', 'like', '%google%');
            })
            ->orderByRaw("CASE WHEN service LIKE '%whatsapp%' OR service LIKE '%wa%' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $activeOrders = $query->limit($limit)->get();

        if ($activeOrders->isEmpty()) {
            $this->info('✅ No active orders to poll.');
            return 0;
        }

        $this->info("📋 Found {$activeOrders->count()} active orders to poll.");

        $smsProviderService = app(SmsProviderService::class);
        $codesReceived = 0;
        $stillWaiting = 0;
        $failed = 0;

        foreach ($activeOrders as $order) {
            try {
                $provider = $order->smsService->provider ?? 'unknown';
                $this->line("  Checking {$order->order_id} ({$provider})...");

                // Get SMS code from provider
                $smsCode = $smsProviderService->getSmsCode($order->smsService, $order->provider_order_id);

                if ($smsCode) {
                    $order->markAsCompleted($smsCode);
                    $this->info("    ✅ SMS code received: {$smsCode}");
                    $codesReceived++;
                    
                    // Update inbox message with SMS code
                    $this->updateInboxMessageWithCode($order);
                    
                    Log::info('SMS Polling: Code received', [
                        'order_id' => $order->order_id,
                        'provider' => $provider,
                        'sms_code' => $smsCode,
                        'user_id' => $order->user_id
                    ]);
                } else {
                    $this->line("    ⏳ Still waiting...");
                    $stillWaiting++;
                }

            } catch (\Exception $e) {
                $this->error("    ❌ Error: {$e->getMessage()}");
                $failed++;
                
                Log::error('SMS Polling: Error', [
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Mark expired orders
        $expiredOrders = SmsOrder::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredOrders as $order) {
            $order->markAsExpired();
            $this->updateInboxMessageExpired($order);
        }

        if ($expiredOrders->count() > 0) {
            $this->warn("⏰ Marked {$expiredOrders->count()} orders as expired.");
        }

        $this->newLine();
        $this->info('📊 Polling Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Codes Received', $codesReceived],
                ['Still Waiting', $stillWaiting],
                ['Failed', $failed],
                ['Expired', $expiredOrders->count()],
            ]
        );

        $this->info('✅ SMS polling complete!');
        
        return 0;
    }

    /**
     * Update inbox message when SMS code is received
     */
    private function updateInboxMessageWithCode(SmsOrder $order): void
    {
        try {
            $inboxMessage = \App\Models\InboxMessage::where('user_id', $order->user_id)
                ->where('reference', $order->order_id)
                ->where('type', 'sms_order')
                ->first();
                
            if ($inboxMessage) {
                $serviceName = $order->getServiceDisplayName();
                $phoneNumber = $order->getFormattedPhoneNumber();
                
                $inboxMessage->update([
                    'title' => "Fadded VIP 🔆  SMS Received - {$serviceName}",
                    'message' => "SMS verification code received for {$phoneNumber} ({$serviceName}). Code: {$order->sms_code}",
                    'metadata' => array_merge($inboxMessage->metadata ?? [], [
                        'sms_code' => $order->sms_code,
                        'status' => $order->status,
                        'status_label' => ucfirst($order->status),
                        'received_at' => $order->received_at?->toISOString()
                    ]),
                    'is_read' => false, // Mark as unread to notify user
                    'updated_at' => now()
                ]);
                
                Log::info('Inbox message updated with SMS code', [
                    'order_id' => $order->order_id,
                    'inbox_message_id' => $inboxMessage->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update inbox message with code', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update inbox message when order expires
     */
    private function updateInboxMessageExpired(SmsOrder $order): void
    {
        try {
            $inboxMessage = \App\Models\InboxMessage::where('user_id', $order->user_id)
                ->where('reference', $order->order_id)
                ->where('type', 'sms_order')
                ->first();
                
            if ($inboxMessage) {
                $serviceName = $order->getServiceDisplayName();
                $phoneNumber = $order->getFormattedPhoneNumber();
                
                $inboxMessage->update([
                    'title' => "Fadded VIP 🔆  SMS Order Expired - {$serviceName}",
                    'message' => "SMS order for {$phoneNumber} ({$serviceName}) has expired without receiving a code. Balance has been refunded.",
                    'metadata' => array_merge($inboxMessage->metadata ?? [], [
                        'status' => $order->status,
                        'status_label' => 'Expired',
                        'expired_at' => now()->toISOString()
                    ]),
                    'updated_at' => now()
                ]);
                
                Log::info('Inbox message updated for expired order', [
                    'order_id' => $order->order_id,
                    'inbox_message_id' => $inboxMessage->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update inbox message for expired order', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

