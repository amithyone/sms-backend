<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VtuNgService;
use App\Services\ElectricitySmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckProcessingElectricity extends Command
{
    protected $signature = 'electricity:check-processing';
    protected $description = 'Check VTU.ng for status of processing electricity transactions';

    private $vtuService;
    private $electricitySmsService;

    public function __construct(VtuNgService $vtuService, ElectricitySmsService $electricitySmsService)
    {
        parent::__construct();
        $this->vtuService = $vtuService;
        $this->electricitySmsService = $electricitySmsService;
    }

    public function handle()
    {
        $this->info('Checking processing electricity transactions...');
        
        // Find all processing VTU orders for electricity
        // Also include 'completed' orders where the response shows 'processing-api' (no token yet)
        $processingOrders = DB::table('vtu_orders')
            ->where('service_type', 'electricity')
            ->where(function($query) {
                $query->where('status', 'processing')
                      ->orWhere(function($q) {
                          $q->where('status', 'completed')
                            ->where(function($q2) {
                                $q2->where('provider_response', 'like', '%"status":"processing-api"%')
                                   ->orWhere('provider_response', 'not like', '%electricity_token%');
                            });
                      });
            })
            ->where('created_at', '>=', now()->subHours(24)) // Only check last 24 hours
            ->get();
        
        if ($processingOrders->isEmpty()) {
            $this->info('No processing electricity transactions found.');
            return 0;
        }
        
        $this->info("Found {$processingOrders->count()} processing transactions. Checking status...");
        
        foreach ($processingOrders as $order) {
            try {
                $this->info("Checking order: {$order->reference}");
                
                // Query VTU.ng for transaction status
                $statusResult = $this->vtuService->getTransactionStatus($order->reference);
                
                if (($statusResult['code'] ?? '') === 'success') {
                    $this->info("✓ Transaction {$order->reference} completed! Updating...");
                    
                    // Extract token data
                    $tokenData = $this->extractElectricityToken($statusResult['data']);
                    
                    // Update VTU order
                    DB::table('vtu_orders')
                        ->where('id', $order->id)
                        ->update([
                            'status' => 'completed',
                            'provider_response' => json_encode($statusResult['data']),
                            'completed_at' => now(),
                            'updated_at' => now(),
                        ]);
                    
                    // Get current transaction data
                    $transaction = DB::table('transactions')->where('reference', $order->reference)->first();
                    $metadata = json_decode($transaction->metadata ?? '{}', true);
                    $metadata['token'] = $tokenData['token'] ?? null;
                    $metadata['units'] = $tokenData['units'] ?? null;
                    $metadata['customer_name'] = $tokenData['customer_name'] ?? null;
                    $metadata['completed_at'] = now()->toIso8601String();
                    
                    // Update transaction
                    DB::table('transactions')
                        ->where('reference', $order->reference)
                        ->update([
                            'status' => 'success',
                            'description' => str_replace(' - PROCESSING', '', $transaction->description),
                            'metadata' => json_encode($metadata),
                            'updated_at' => now(),
                        ]);
                    
                    // Get user details
                    $user = DB::table('users')->where('id', $order->user_id)->first();
                    
                    // Build receipt message with all available data
                    $receiptLines = [];
                    $receiptLines[] = "━━━━━━━━━━━━━━━━━━━━━━━━";
                    $receiptLines[] = "⚡ ELECTRICITY PURCHASE RECEIPT";
                    $receiptLines[] = "━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    
                    // Token (most important)
                    if ($tokenData['token']) {
                        $receiptLines[] = "🔆 TOKEN: " . $tokenData['token'];
                        $receiptLines[] = "";
                    }
                    
                    // Customer details
                    $receiptLines[] = "CUSTOMER INFORMATION:";
                    if ($tokenData['customer_name']) {
                        $receiptLines[] = "👤 Name: " . $tokenData['customer_name'];
                    }
                    $receiptLines[] = "🔢 Meter Number: " . $order->phone_number;
                    if ($tokenData['address']) {
                        $receiptLines[] = "📍 Address: " . $tokenData['address'];
                    }
                    
                    // Transaction details from metadata
                    $metaData = $statusResult['data']['meta_data'] ?? [];
                    if (!empty($metaData['electricity'])) {
                        $receiptLines[] = "⚡ Provider: " . $metaData['electricity'];
                    }
                    if (!empty($metaData['meter_type'])) {
                        $receiptLines[] = "📊 Meter Type: " . ucfirst($metaData['meter_type']);
                    }
                    if (!empty($metaData['customer_arrears']) && $metaData['customer_arrears'] > 0) {
                        $receiptLines[] = "⚠️ Arrears: ₦" . number_format($metaData['customer_arrears']);
                    }
                    
                    $receiptLines[] = "";
                    $receiptLines[] = "PAYMENT INFORMATION:";
                    $receiptLines[] = "💰 Amount: ₦" . number_format($order->amount);
                    
                    // Units if available
                    if ($tokenData['units']) {
                        $receiptLines[] = "⚡ Units: " . $tokenData['units'] . " kWh";
                    }
                    
                    $receiptLines[] = "";
                    $receiptLines[] = "TRANSACTION DETAILS:";
                    $receiptLines[] = "📝 Reference: " . $order->reference;
                    $receiptLines[] = "📅 Date: " . date('d M Y, h:i A', strtotime($statusResult['data']['date_updated'] ?? now()));
                    $receiptLines[] = "✅ Status: Completed";
                    $receiptLines[] = "";
                    $receiptLines[] = "━━━━━━━━━━━━━━━━━━━━━━━━";
                    $receiptLines[] = "Keep this receipt for your records";
                    
                    $receiptMessage = implode("\n", $receiptLines);
                    
                    // Create inbox message with full receipt
                    DB::table('inbox_messages')->insert([
                        'user_id' => $order->user_id,
                        'type' => 'electricity',
                        'title' => 'Electricity Token Ready! ⚡',
                        'message' => $receiptMessage,
                        'metadata' => json_encode([
                            'reference' => $order->reference,
                            'token' => $tokenData['token'],
                            'units' => $tokenData['units'],
                            'customer_name' => $tokenData['customer_name'],
                            'address' => $tokenData['address'],
                            'meter_number' => $order->phone_number,
                            'meter_type' => $metaData['meter_type'] ?? null,
                            'provider' => $metaData['electricity'] ?? null,
                            'amount' => $order->amount,
                            'amount_charged' => $statusResult['data']['amount_charged'] ?? $order->amount,
                            'arrears' => $metaData['customer_arrears'] ?? 0,
                            'order_id' => $statusResult['data']['order_id'] ?? null,
                            'date_completed' => $statusResult['data']['date_updated'] ?? now(),
                            'status' => 'completed',
                            'receipt_type' => 'electricity'
                        ]),
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Send SMS notification
                    if ($user) {
                        try {
                            $userObj = (object) [
                                'id' => $user->id,
                                'name' => $user->name,
                                'phone' => $user->phone ?? null,
                                'balance' => $user->balance ?? 0,
                            ];
                            $smsData = array_merge($tokenData, [
                                'amount' => $order->amount,
                                'service_id' => $order->network,
                                'customer_id' => $order->phone_number,
                            ]);
                            $this->electricitySmsService->sendElectricityTokenSms($userObj, $smsData, $order->reference);
                            $this->info("  SMS sent to user");
                        } catch (\Exception $e) {
                            Log::error('Failed to send SMS for completed electricity', [
                                'reference' => $order->reference,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    $this->info("✓ Order {$order->reference} updated successfully!");
                    
                } else {
                    $this->warn("  Transaction {$order->reference} still processing or failed");
                    Log::info('Electricity transaction still processing', [
                        'reference' => $order->reference,
                        'status_result' => $statusResult
                    ]);
                }
                
            } catch (\Exception $e) {
                $this->error("✗ Error checking order {$order->reference}: {$e->getMessage()}");
                Log::error('Error checking processing electricity transaction', [
                    'reference' => $order->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info('Completed checking processing electricity transactions.');
        return 0;
    }
    
    private function extractElectricityToken($data): array
    {
        $token = null;
        $units = null;
        $customerName = null;
        $address = null;
        $tokenType = null;
        
        if (is_array($data)) {
            // Try different possible structures
            $token = $data['token'] ?? $data['Token'] ?? $data['token_code'] ?? $data['TokenCode'] ?? null;
            $units = $data['units'] ?? $data['Units'] ?? $data['unit'] ?? $data['Unit'] ?? null;
            $customerName = $data['customer_name'] ?? $data['CustomerName'] ?? $data['customerName'] ?? null;
            $address = $data['address'] ?? $data['Address'] ?? null;
            $tokenType = $data['token_type'] ?? $data['TokenType'] ?? 'Standard';
            
            // Check if token is in a nested 'data' field
            if (!$token && isset($data['data'])) {
                $nestedData = $data['data'];
                $token = $nestedData['token'] ?? $nestedData['Token'] ?? null;
                $units = $nestedData['units'] ?? $nestedData['Units'] ?? null;
                $customerName = $nestedData['customer_name'] ?? $nestedData['CustomerName'] ?? null;
                $address = $nestedData['address'] ?? $nestedData['Address'] ?? null;
            }
            
            // Check meta_data field (VTU.ng specific structure)
            if (!$token && isset($data['meta_data'])) {
                $metaData = $data['meta_data'];
                $token = $metaData['electricity_token'] ?? $metaData['token'] ?? null;
                $customerName = $customerName ?? $metaData['customer_name'] ?? null;
                $address = $address ?? $metaData['customer_address'] ?? null;
                // Don't estimate units - if VTU doesn't provide, leave as null
            }
        }
        
        return [
            'token' => $token,
            'token_type' => $tokenType,
            'units' => $units,
            'customer_name' => $customerName,
            'address' => $address,
        ];
    }
}

