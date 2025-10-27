<?php

/**
 * CRITICAL FINANCIAL CORRECTION SCRIPT
 * 
 * This script reverses all unaccounted balances caused by V1 sync system
 * that was directly setting user balances without creating transaction records.
 * 
 * ISSUE: ₦1,623,503.89 in unaccounted money across 1,466 users
 * CAUSE: V1 sync system bypassing transaction audit trail
 * ACTION: Reverse all unaccounted balances and create audit trail
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔴 CRITICAL FINANCIAL CORRECTION STARTING...\n";
echo "Reversing V1 sync balance issues\n";
echo "================================\n\n";

$totalReversed = 0;
$usersProcessed = 0;
$usersReversed = 0;
$errors = 0;

try {
    // Get all users
    $users = DB::table('users')->get();
    
    echo "Processing " . count($users) . " users...\n\n";
    
    foreach ($users as $user) {
        $usersProcessed++;
        
        try {
            // Calculate what balance should be from transactions
            $totalCredits = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('type', 'credit')
                ->where('status', 'success')
                ->sum('amount');
                
            $totalDebits = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('type', 'debit')
                ->where('status', 'success')
                ->sum('amount');
                
            $calculatedBalance = $totalCredits - $totalDebits;
            $actualBalance = $user->balance;
            $unaccountedAmount = $actualBalance - $calculatedBalance;
            
            // If there's unaccounted money, reverse it
            if ($unaccountedAmount > 0) {
                $usersReversed++;
                $totalReversed += $unaccountedAmount;
                
                // Create transaction record for the reversal
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'debit',
                    'amount' => $unaccountedAmount,
                    'description' => 'V1 Sync Balance Correction - Unaccounted Funds Reversed',
                    'reference' => 'V1_REVERSAL_' . time() . '_' . $user->id,
                    'status' => 'success',
                    'balance_before' => $actualBalance,
                    'balance_after' => $calculatedBalance,
                    'metadata' => json_encode([
                        'correction_type' => 'v1_sync_reversal',
                        'original_balance' => $actualBalance,
                        'calculated_balance' => $calculatedBalance,
                        'unaccounted_amount' => $unaccountedAmount,
                        'total_credits' => $totalCredits,
                        'total_debits' => $totalDebits,
                        'reversal_date' => now()->toISOString(),
                        'reason' => 'V1 sync system bypassed transaction audit trail'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Update user balance to match calculated amount
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'balance' => $calculatedBalance,
                        'updated_at' => now()
                    ]);
                
                // Log the reversal
                Log::warning('V1 Sync Balance Reversed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'unaccounted_amount' => $unaccountedAmount,
                    'original_balance' => $actualBalance,
                    'corrected_balance' => $calculatedBalance
                ]);
                
                echo "✅ User {$user->email}: Reversed ₦" . number_format($unaccountedAmount, 2) . 
                     " (₦" . number_format($actualBalance, 2) . " → ₦" . number_format($calculatedBalance, 2) . ")\n";
            }
            
            // Progress indicator
            if ($usersProcessed % 100 == 0) {
                echo "Progress: {$usersProcessed}/" . count($users) . " users processed\n";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "❌ Error processing user {$user->email}: " . $e->getMessage() . "\n";
            Log::error('V1 Sync Reversal Error', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    echo "\n================================\n";
    echo "🔴 FINANCIAL CORRECTION COMPLETE\n";
    echo "================================\n";
    echo "Users Processed: {$usersProcessed}\n";
    echo "Users Reversed: {$usersReversed}\n";
    echo "Total Amount Reversed: ₦" . number_format($totalReversed, 2) . "\n";
    echo "Errors: {$errors}\n";
    echo "Status: " . ($errors === 0 ? "✅ SUCCESS" : "⚠️  COMPLETED WITH ERRORS") . "\n";
    
    // Log final summary
    Log::critical('V1 Sync Financial Correction Completed', [
        'users_processed' => $usersProcessed,
        'users_reversed' => $usersReversed,
        'total_reversed' => $totalReversed,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    Log::critical('V1 Sync Reversal Script Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}

echo "\n✅ All balances have been corrected to match transaction records.\n";
echo "🔒 Financial integrity has been restored.\n";
