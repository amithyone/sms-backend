<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;

class ReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $referralService = new ReferralService();
        
        $users = User::whereNull('referral_code')->get();
        
        $count = 0;
        foreach ($users as $user) {
            try {
                $code = $referralService->generateReferralCode($user);
                $user->update(['referral_code' => $code]);
                
                // Initialize referral stats for the user
                $referralService->initializeReferralStats($user->id);
                
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to generate referral code for user {$user->id}: {$e->getMessage()}");
            }
        }
        
        $this->command->info("Generated referral codes for {$count} users.");
    }
}
