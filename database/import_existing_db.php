<?php

/**
 * Database Import Script for Existing SMS App
 * 
 * This script helps you import your existing database while preserving:
 * - User passwords (assuming they're hashed with bcrypt)
 * - User balances
 * - User data and relationships
 * 
 * Usage:
 * 1. Export your existing database to SQL
 * 2. Update the configuration below
 * 3. Run: php database/import_existing_db.php
 */

// Configuration - UPDATE THESE VALUES
$config = [
    'old_database' => [
        'host' => 'localhost',
        'database' => 'your_old_database_name',
        'username' => 'your_old_db_username',
        'password' => 'your_old_db_password',
        'port' => 3306
    ],
    'new_database' => [
        'host' => 'localhost',
        'database' => 'newsms_db',
        'username' => 'root',
        'password' => '',
        'port' => 3306
    ],
    'table_mappings' => [
        // Map your old table names to new table names
        'old_users_table' => 'users',
        'old_transactions_table' => 'transactions',
        'old_services_table' => 'services',
        // Add more mappings as needed
    ],
    'column_mappings' => [
        // Map your old column names to new column names
        'users' => [
            'old_id' => 'id',
            'old_name' => 'name',
            'old_email' => 'email',
            'old_phone' => 'phone',
            'old_username' => 'username',
            'old_password' => 'password',
            'old_balance' => 'balance',
            'old_wallet_balance' => 'wallet_balance',
            'old_status' => 'status',
            'old_role' => 'role',
            'old_created_at' => 'created_at',
            'old_updated_at' => 'updated_at',
        ],
        'transactions' => [
            'old_id' => 'id',
            'old_user_id' => 'user_id',
            'old_type' => 'type',
            'old_amount' => 'amount',
            'old_description' => 'description',
            'old_reference' => 'reference',
            'old_status' => 'status',
            'old_created_at' => 'created_at',
            'old_updated_at' => 'updated_at',
        ]
    ]
];

class DatabaseImporter
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
        echo "\n🔄 Importing users...\n";

        try {
            // Get users from old database
            $oldUsersTable = $this->config['table_mappings']['old_users_table'] ?? 'users';
            $stmt = $this->oldDb->query("SELECT * FROM {$oldUsersTable}");
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
                    echo "✅ Imported user: {$newUser['email']}\n";

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
        echo "\n🔄 Importing transactions...\n";

        try {
            $oldTransactionsTable = $this->config['table_mappings']['old_transactions_table'] ?? 'transactions';
            $stmt = $this->oldDb->query("SELECT * FROM {$oldTransactionsTable}");
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
                    echo "✅ Imported transaction: {$newTransaction['reference']}\n";

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

    private function mapUserColumns($oldUser)
    {
        $mapping = $this->config['column_mappings']['users'];
        
        return [
            'id' => $oldUser[$mapping['old_id']] ?? null,
            'name' => $oldUser[$mapping['old_name']] ?? '',
            'email' => $oldUser[$mapping['old_email']] ?? '',
            'phone' => $oldUser[$mapping['old_phone']] ?? null,
            'username' => $oldUser[$mapping['old_username']] ?? null,
            'password' => $oldUser[$mapping['old_password']] ?? '', // Preserve existing hash
            'balance' => $oldUser[$mapping['old_balance']] ?? 0.00,
            'wallet_balance' => $oldUser[$mapping['old_wallet_balance']] ?? 0.00,
            'status' => $this->mapStatus($oldUser[$mapping['old_status']] ?? 'active'),
            'role' => $this->mapRole($oldUser[$mapping['old_role']] ?? 'user'),
            'referral_code' => $this->generateReferralCode(),
            'created_at' => $oldUser[$mapping['old_created_at']] ?? now(),
            'updated_at' => $oldUser[$mapping['old_updated_at']] ?? now(),
        ];
    }

    private function mapTransactionColumns($oldTransaction)
    {
        $mapping = $this->config['column_mappings']['transactions'];
        
        return [
            'id' => $oldTransaction[$mapping['old_id']] ?? null,
            'user_id' => $oldTransaction[$mapping['old_user_id']] ?? null,
            'type' => $this->mapTransactionType($oldTransaction[$mapping['old_type']] ?? 'credit'),
            'amount' => $oldTransaction[$mapping['old_amount']] ?? 0.00,
            'balance_before' => 0.00, // You might need to calculate this
            'balance_after' => 0.00,  // You might need to calculate this
            'description' => $oldTransaction[$mapping['old_description']] ?? '',
            'reference' => $oldTransaction[$mapping['old_reference']] ?? $this->generateReference(),
            'status' => $this->mapTransactionStatus($oldTransaction[$mapping['old_status']] ?? 'success'),
            'created_at' => $oldTransaction[$mapping['old_created_at']] ?? now(),
            'updated_at' => $oldTransaction[$mapping['old_updated_at']] ?? now(),
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
}

// Run the import
echo "🚀 Starting database import...\n";
echo "⚠️  Please make sure you have:\n";
echo "   1. Backed up your existing database\n";
echo "   2. Updated the configuration in this script\n";
echo "   3. Created the new database 'newsms_db'\n";
echo "   4. Run Laravel migrations first\n\n";

echo "Press Enter to continue or Ctrl+C to cancel...";
fgets(STDIN);

$importer = new DatabaseImporter($config);

// Import users first
$importer->importUsers();

// Import transactions
$importer->importTransactions();

echo "\n🎉 Database import completed!\n";
echo "Next steps:\n";
echo "1. Verify the imported data\n";
echo "2. Test user login with existing credentials\n";
echo "3. Check that balances are correct\n";
echo "4. Update any missing referral codes if needed\n";
