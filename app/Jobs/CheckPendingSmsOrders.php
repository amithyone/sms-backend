<?php

namespace App\Jobs;

use App\Models\SmsOrder;
use App\Services\SmsProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckPendingSmsOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SmsProviderService $smsProviderService): void
    {
        Log::info('=== CheckPendingSmsOrders Job Started ===');
        
        // Get all pending/active orders that are not expired
        $pendingOrders = SmsOrder::whereIn('status', ['pending', 'active'])
            ->where('expires_at', '>', now())
            ->with('smsService')
            ->get();
            
        Log::info('Found pending SMS orders', ['count' => $pendingOrders->count()]);
        
        foreach ($pendingOrders as $order) {
            try {
                // Skip if order is expired
                if ($order->isExpired()) {
                    Log::info('Order expired, marking as expired', ['order_id' => $order->order_id]);
                    $order->markAsExpired();
                    $this->updateInboxMessageExpired($order);
                    continue;
                }
                
                // Skip if order is already completed
                if ($order->isCompleted()) {
                    continue;
                }
                
                // Check for SMS code from provider
                Log::info('Checking SMS code for order', [
                    'order_id' => $order->order_id,
                    'provider' => $order->smsService->provider ?? 'unknown',
                    'provider_order_id' => $order->provider_order_id
                ]);
                
                $smsCode = $smsProviderService->getSmsCode($order->smsService, $order->provider_order_id);
                
                if ($smsCode) {
                    Log::info('SMS code received!', [
                        'order_id' => $order->order_id,
                        'provider' => $order->smsService->provider,
                        'code_length' => strlen($smsCode)
                    ]);
                    
                    // Mark order as completed
                    $order->markAsCompleted($smsCode);
                    
                    // Update inbox message with SMS code
                    $this->updateInboxMessageWithCode($order);
                    
                    Log::info('Order completed and inbox updated', [
                        'order_id' => $order->order_id,
                        'user_id' => $order->user_id
                    ]);
                } else {
                    Log::debug('No SMS code yet for order', [
                        'order_id' => $order->order_id,
                        'provider' => $order->smsService->provider
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('Error checking SMS code for order', [
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('=== CheckPendingSmsOrders Job Completed ===');
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
            } else {
                Log::warning('Inbox message not found for order', [
                    'order_id' => $order->order_id,
                    'user_id' => $order->user_id
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
