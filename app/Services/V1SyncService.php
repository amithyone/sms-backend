<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class V1SyncService
{
    private $apiUrl;
    private $apiKey;
    private $debug;
    private $isEnabled;

    public function __construct()
    {
        $this->apiUrl = env('V1_API_URL');
        $this->apiKey = env('V1_SYNC_API_KEY');
        $this->debug = env('V1_SYNC_DEBUG', false);
        $this->isEnabled = env('V1_SYNC_ENABLED', true); // Enabled for LOGIN only - balance sync disabled
    }

    /**
     * Get user data from V1 by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getUserFromV1($email)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-V2-Sync-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($this->apiUrl . '/get-user', [
                    'email' => $email
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($this->debug) {
                    Log::info('V1 Sync: User retrieved', [
                        'email' => $email,
                        'found' => $result['status'] ?? false
                    ]);
                }
                
                return $result['status'] ? $result['data'] : null;
            }

            Log::error('V1 Sync: Failed to get user', [
                'email' => $email,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('V1 Sync: Error getting user', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Authenticate user with V1 credentials
     * 
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function authenticateUser($email, $password)
    {
        // Get user data from V1
        $v1UserData = $this->getUserFromV1($email);

        if (!$v1UserData) {
            Log::warning('V1 Sync: User not found on V1', ['email' => $email]);
            return null;
        }

        // Verify password against V1 hash
        if (!Hash::check($password, $v1UserData['password_hash'])) {
            Log::warning('V1 Sync: Invalid password', ['email' => $email]);
            return null;
        }

        // Check if user exists locally and preserve admin role
        $existingUser = User::where('email', $email)->first();
        $localRole = ($existingUser && in_array($existingUser->role, ['admin', 'super_admin'])) 
            ? $existingUser->role 
            : ($v1UserData['role'] ?? 'user');

        // Create or update user in V2 database
        // IMPORTANT: Do NOT sync balance - only sync user data for authentication
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $v1UserData['name'] ?? $v1UserData['username'] ?? 'User',
                'password' => $v1UserData['password_hash'], // Keep same hash
                'v1_user_id' => $v1UserData['id'],
                // DO NOT TOUCH BALANCE - users already have their migrated balance
                'email_verified_at' => $v1UserData['email_verified_at'],
                'username' => $v1UserData['username'] ?? null,
                'phone' => $v1UserData['phone'] ?? null,
                'status' => 'active', // Ensure synced users are active
                'role' => $localRole, // Preserve local admin role if set
            ]
        );
        
        // BALANCE SYNC DISABLED - Users already have their V1 balances migrated
        // No balance updates or transaction records created during login

        Log::info('V1 Sync: User authenticated successfully', [
            'v2_user_id' => $user->id,
            'v1_user_id' => $v1UserData['id'],
            'email' => $email
        ]);

        return $user;
    }

    /**
     * Update user balance on V1
     * 
     * @param string $email User email
     * @param float $amount Amount to debit/credit
     * @param string $type 'debit' or 'credit'
     * @param string $description Transaction description
     * @param string $reference Unique transaction reference
     * @return array
     */
    public function updateBalanceOnV1($email, $amount, $type, $description, $reference)
    {
        throw new \Exception('V1 Sync is permanently disabled. Balance updates are now handled locally only.');
        
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-V2-Sync-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($this->apiUrl . '/update-balance', [
                    'email' => $email,
                    'amount' => $amount,
                    'type' => $type,
                    'description' => $description,
                    'reference' => $reference
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['status']) {
                    // BALANCE SYNC DISABLED - Do not update local balance from V1
                    // V1 balance updates are ignored to prevent duplicate credits
                    Log::warning('V1 balance update ignored - balance sync is disabled', [
                        'email' => $email,
                        'amount' => $amount,
                        'type' => $type,
                        'reference' => $reference
                    ]);

                    Log::info('V1 Sync: Balance updated successfully', [
                        'email' => $email,
                        'amount' => $amount,
                        'type' => $type,
                        'old_balance' => $result['data']['old_balance'] ?? null,
                        'new_balance' => $result['data']['new_balance'] ?? null,
                        'reference' => $reference
                    ]);

                    return $result;
                } else {
                    Log::error('V1 Sync: Balance update rejected', [
                        'email' => $email,
                        'message' => $result['message'] ?? 'Unknown error'
                    ]);
                }
            }

            Log::error('V1 Sync: Failed to update balance', [
                'email' => $email,
                'amount' => $amount,
                'type' => $type,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'Failed to sync balance with V1'
            ];

        } catch (\Exception $e) {
            Log::error('V1 Sync: Error updating balance', [
                'email' => $email,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Error syncing balance: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user exists on V1
     * 
     * @param string $email
     * @return bool
     */
    public function userExistsOnV1($email)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-V2-Sync-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/verify-user', [
                    'email' => $email
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['exists'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('V1 Sync: Error checking user existence', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync user data from V1 (refresh local copy)
     * 
     * @param string $email
     * @return bool
     */
    public function syncUserData($email)
    {
        $v1UserData = $this->getUserFromV1($email);
        
        if (!$v1UserData) {
            return false;
        }

        $user = User::where('email', $email)->first();
        
        if ($user) {
            // BALANCE SYNC DISABLED - Only update user info, not balance
            $user->update([
                'name' => $v1UserData['name'] ?? $v1UserData['username'] ?? $user->name,
                'v1_user_id' => $v1UserData['id'],
                // DO NOT UPDATE BALANCE
            ]);
            
            return true;
        }

        return false;
    }
}

