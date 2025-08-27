<?php

/**
 * WorldHome SMS Database Import Script
 * 
 * This script imports data from your existing worldhome_sms database
 * to the new Laravel backend while preserving all user data and balances.
 */

// Configuration - UPDATE THESE VALUES
$config = [
    'old_database' => [
        'host' => 'localhost',
        'database' => 'worldhome_sms', // Your existing database name
        'username' => 'root',          // Your database username
        'password' => '',              // Your database password
        'port' => 3306
    ],
    'new_database' => [
        'host' => 'localhost',
        'database' => 'newsms_db',     // New Laravel database
        'username' => 'root',          // New database username
        'password' => '',              // New database password
        'port' => 3306
    ]
];

class WorldHomeSMSImporter
{
    private $oldDb;
    private $newDb;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->connectDatabases();
    }

    private function connectDatabases()
    {
        try {
            // Connect to old database
            $this->oldDb = new PDO(
                "mysql:host={$this->config['old_database']['host']};dbname={$this->config['old_database']['database']};port={$this->config['old_database']['port']}",
                $this->config['old_database']['username'],
                $this->config['old_database']['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Connect to new database
            $this->newDb = new PDO(
                "mysql:host={$this->config['new_database']['host']};dbname={$this->config['new_database']['database']};port={$this->config['new_database']['port']}",
                $this->config['new_database']['username'],
                $this->config['new_database']['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            echo "✅ Connected to both databases successfully\n";
        } catch (PDOException $e) {
            die("❌ Database connection failed: " . $e->getMessage() . "\n");
        }
    }

    public function importUsers()
    {
        echo "\n🔄 Importing users from worldhome_sms...\n";

        try {
            // Get users from old database
            $stmt = $this->oldDb->query("SELECT * FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $imported = 0;
            $skipped = 0;

            foreach ($users as $user) {
                try {
                    // Map old columns to new columns
                    $newUser = $this->mapUserColumns($user);
                    
                    // Check if user already exists
                    $existingUser = $this->newDb->prepare("SELECT id FROM users WHERE email = ?");
                    $existingUser->execute([$newUser['email']]);
                    
                    if ($existingUser->fetch()) {
                        echo "⚠️  User {$newUser['email']} already exists, skipping...\n";
                        $skipped++;
                        continue;
                    }

                    // Insert user into new database
                    $columns = implode(', ', array_keys($newUser));
                    $placeholders = ':' . implode(', :', array_keys($newUser));
                    
                    $stmt = $this->newDb->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
                    $stmt->execute($newUser);

                    $imported++;
                    echo "✅ Imported user: {$newUser['email']} (Balance: {$newUser['balance']})\n";

                } catch (Exception $e) {
                    echo "❌ Error importing user {$user['email'] ?? 'unknown'}: " . $e->getMessage() . "\n";
                }
            }

            echo "\n📊 Users import summary:\n";
            echo "   Imported: {$imported}\n";
            echo "   Skipped: {$skipped}\n";

        } catch (Exception $e) {
            echo "❌ Error during users import: " . $e->getMessage() . "\n";
        }
    }

    public function importTransactions()
    {
        echo "\n🔄 Importing transactions from worldhome_sms...\n";

        try {
            $stmt = $this->oldDb->query("SELECT * FROM transactions");
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $imported = 0;
            $skipped = 0;

            foreach ($transactions as $transaction) {
                try {
                    $newTransaction = $this->mapTransactionColumns($transaction);
                    
                    // Check if transaction already exists
                    $existingTransaction = $this->newDb->prepare("SELECT id FROM transactions WHERE reference = ?");
                    $existingTransaction->execute([$newTransaction['reference']]);
                    
                    if ($existingTransaction->fetch()) {
                        echo "⚠️  Transaction {$newTransaction['reference']} already exists, skipping...\n";
                        $skipped++;
                        continue;
                    }

                    // Insert transaction
                    $columns = implode(', ', array_keys($newTransaction));
                    $placeholders = ':' . implode(', :', array_keys($newTransaction));
                    
                    $stmt = $this->newDb->prepare("INSERT INTO transactions ({$columns}) VALUES ({$placeholders})");
                    $stmt->execute($newTransaction);

                    $imported++;
                    echo "✅ Imported transaction: {$newTransaction['reference']} ({$newTransaction['type']} - {$newTransaction['amount']})\n";

                } catch (Exception $e) {
                    echo "❌ Error importing transaction: " . $e->getMessage() . "\n";
                }
            }

            echo "\n📊 Transactions import summary:\n";
            echo "   Imported: {$imported}\n";
            echo "   Skipped: {$skipped}\n";

        } catch (Exception $e) {
            echo "❌ Error during transactions import: " . $e->getMessage() . "\n";
        }
    }

    public function importServices()
    {
        echo "\n🔄 Importing services...\n";

        try {
            // Create default services if they don't exist
            $defaultServices = [
                [
                    'name' => 'Fadded VIP 🔆 Virtual Verify Number',
                    'description' => 'Get virtual numbers for SMS verification from 200+ services worldwide',
                    'price' => 150.00,
                    'category' => 'verification',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 Buy Airtime',
                    'description' => 'Instant airtime top-up for all Nigerian networks (MTN, Airtel, Glo, 9mobile)',
                    'price' => 100.00,
                    'category' => 'telecom',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 Data Bundles',
                    'description' => 'Purchase data bundles for all networks with competitive pricing',
                    'price' => 300.00,
                    'category' => 'telecom',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 DSTV/GoTV/StarTimes',
                    'description' => 'Pay for cable TV subscriptions and renew your packages instantly',
                    'price' => 1100.00,
                    'category' => 'entertainment',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 Betting Wallet',
                    'description' => 'Fund your betting accounts on popular platforms like Bet9ja, SportyBet',
                    'price' => 500.00,
                    'category' => 'gaming',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 Electricity Bills',
                    'description' => 'Pay electricity bills for all major Nigerian power distribution companies',
                    'price' => 1000.00,
                    'category' => 'utilities',
                    'is_active' => true
                ],
                [
                    'name' => 'Fadded VIP 🔆 Recharge Cards',
                    'description' => 'Generate and download recharge cards for all networks instantly',
                    'price' => 100.00,
                    'category' => 'telecom',
                    'is_active' => true
                ]
            ];

            $imported = 0;
            foreach ($defaultServices as $service) {
                try {
                    // Check if service already exists
                    $existingService = $this->newDb->prepare("SELECT id FROM services WHERE name = ?");
                    $existingService->execute([$service['name']]);
                    
                    if ($existingService->fetch()) {
                        echo "⚠️  Service '{$service['name']}' already exists, skipping...\n";
                        continue;
                    }

                    // Insert service
                    $columns = implode(', ', array_keys($service));
                    $placeholders = ':' . implode(', :', array_keys($service));
                    
                    $stmt = $this->newDb->prepare("INSERT INTO services ({$columns}) VALUES ({$placeholders})");
                    $stmt->execute($service);

                    $imported++;
                    echo "✅ Created service: {$service['name']}\n";

                } catch (Exception $e) {
                    echo "❌ Error creating service: " . $e->getMessage() . "\n";
                }
            }

            echo "\n📊 Services import summary:\n";
            echo "   Created: {$imported}\n";

        } catch (Exception $e) {
            echo "❌ Error during services import: " . $e->getMessage() . "\n";
        }
    }

    private function mapUserColumns($oldUser)
    {
        return [
            'id' => $oldUser['id'] ?? null,
            'name' => $oldUser['name'] ?? $oldUser['username'] ?? 'User',
            'email' => $oldUser['email'] ?? $oldUser['username'] . '@example.com',
            'phone' => $oldUser['phone'] ?? $oldUser['mobile'] ?? null,
            'username' => $oldUser['username'] ?? null,
            'password' => $oldUser['password'] ?? '', // Preserve existing hash
            'balance' => $oldUser['balance'] ?? $oldUser['wallet_balance'] ?? 0.00,
            'wallet_balance' => $oldUser['wallet_balance'] ?? $oldUser['balance'] ?? 0.00,
            'status' => $this->mapStatus($oldUser['status'] ?? $oldUser['is_active'] ?? 'active'),
            'role' => $this->mapRole($oldUser['role'] ?? $oldUser['user_type'] ?? 'user'),
            'referral_code' => $this->generateReferralCode(),
            'account_number' => $oldUser['account_number'] ?? null,
            'bank_name' => $oldUser['bank_name'] ?? null,
            'account_name' => $oldUser['account_name'] ?? null,
            'created_at' => $oldUser['created_at'] ?? $oldUser['date_created'] ?? now(),
            'updated_at' => $oldUser['updated_at'] ?? $oldUser['date_updated'] ?? now(),
        ];
    }

    private function mapTransactionColumns($oldTransaction)
    {
        return [
            'id' => $oldTransaction['id'] ?? null,
            'user_id' => $oldTransaction['user_id'] ?? $oldTransaction['userid'] ?? null,
            'type' => $this->mapTransactionType($oldTransaction['type'] ?? $oldTransaction['transaction_type'] ?? 'credit'),
            'amount' => $oldTransaction['amount'] ?? 0.00,
            'balance_before' => $oldTransaction['balance_before'] ?? 0.00,
            'balance_after' => $oldTransaction['balance_after'] ?? 0.00,
            'description' => $oldTransaction['description'] ?? $oldTransaction['narration'] ?? 'Transaction',
            'reference' => $oldTransaction['reference'] ?? $oldTransaction['ref'] ?? $this->generateReference(),
            'status' => $this->mapTransactionStatus($oldTransaction['status'] ?? 'success'),
            'fee' => $oldTransaction['fee'] ?? 0.00,
            'total_amount' => $oldTransaction['total_amount'] ?? $oldTransaction['amount'] ?? 0.00,
            'created_at' => $oldTransaction['created_at'] ?? $oldTransaction['date_created'] ?? now(),
            'updated_at' => $oldTransaction['updated_at'] ?? $oldTransaction['date_updated'] ?? now(),
        ];
    }

    private function mapStatus($oldStatus)
    {
        $statusMap = [
            'active' => 'active',
            'inactive' => 'inactive',
            'suspended' => 'suspended',
            'blocked' => 'suspended',
            'banned' => 'suspended',
            '1' => 'active',
            '0' => 'inactive',
        ];

        return $statusMap[strtolower($oldStatus)] ?? 'active';
    }

    private function mapRole($oldRole)
    {
        $roleMap = [
            'user' => 'user',
            'admin' => 'admin',
            'super_admin' => 'super_admin',
            'administrator' => 'admin',
            'moderator' => 'admin',
            '1' => 'admin',
            '0' => 'user',
        ];

        return $roleMap[strtolower($oldRole)] ?? 'user';
    }

    private function mapTransactionType($oldType)
    {
        $typeMap = [
            'credit' => 'credit',
            'debit' => 'debit',
            'transfer' => 'transfer',
            'withdrawal' => 'withdrawal',
            'deposit' => 'deposit',
            'purchase' => 'service_purchase',
            'refund' => 'refund',
            'commission' => 'commission',
            'airtime' => 'service_purchase',
            'data' => 'service_purchase',
            'cable' => 'service_purchase',
        ];

        return $typeMap[strtolower($oldType)] ?? 'credit';
    }

    private function mapTransactionStatus($oldStatus)
    {
        $statusMap = [
            'success' => 'success',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'completed' => 'success',
            'processing' => 'pending',
            '1' => 'success',
            '0' => 'pending',
        ];

        return $statusMap[strtolower($oldStatus)] ?? 'success';
    }

    private function generateReferralCode()
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    private function generateReference()
    {
        return 'TXN' . time() . rand(1000, 9999);
    }

    public function showDatabaseInfo()
    {
        echo "\n📊 Database Information:\n";
        
        try {
            // Old database info
            $stmt = $this->oldDb->query("SELECT COUNT(*) as user_count FROM users");
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
            
            $stmt = $this->oldDb->query("SELECT COUNT(*) as transaction_count FROM transactions");
            $transactionCount = $stmt->fetch(PDO::FETCH_ASSOC)['transaction_count'];
            
            echo "   Old Database (worldhome_sms):\n";
            echo "     Users: {$userCount}\n";
            echo "     Transactions: {$transactionCount}\n";
            
        } catch (Exception $e) {
            echo "   Error getting old database info: " . $e->getMessage() . "\n";
        }
    }
}

// Run the import
echo "🚀 Starting WorldHome SMS Database Import...\n";
echo "⚠️  Please make sure you have:\n";
echo "   1. Backed up your existing database\n";
echo "   2. Updated the configuration in this script\n";
echo "   3. Created the new database 'newsms_db'\n";
echo "   4. Run Laravel migrations first\n\n";

echo "Press Enter to continue or Ctrl+C to cancel...";
fgets(STDIN);

$importer = new WorldHomeSMSImporter($config);

// Show database information
$importer->showDatabaseInfo();

// Import users first
$importer->importUsers();

// Import transactions
$importer->importTransactions();

// Import services
$importer->importServices();

echo "\n🎉 WorldHome SMS Database import completed!\n";
echo "Next steps:\n";
echo "1. Verify the imported data\n";
echo "2. Test user login with existing credentials\n";
echo "3. Check that balances are correct\n";
echo "4. Update any missing referral codes if needed\n";
echo "5. Start your Laravel backend: php artisan serve\n";
echo "6. Start your React frontend: npm run dev\n";
