<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Importing Users via Direct SQL ===\n\n";

try {
    // First, clear existing users (delete one by one to avoid foreign key issues)
    echo "1. Clearing existing users...\n";
    $existingUsers = \App\Models\User::count();
    
    // Delete users in chunks to avoid memory issues
    \App\Models\User::chunk(100, function($users) {
        foreach($users as $user) {
            $user->delete();
        }
    });
    
    echo "   ✓ Cleared {$existingUsers} existing users\n\n";
    
    // Read the SQL file
    $sqlFile = 'database/wolrdhome_sms.sql';
    $content = file_get_contents($sqlFile);
    
    if (!$content) {
        throw new Exception("Could not read SQL file: {$sqlFile}");
    }
    
    echo "2. SQL file loaded successfully\n";
    
    // Count total INSERT statements for users
    $totalInserts = substr_count($content, 'INSERT INTO `users`');
    echo "   ✓ Found {$totalInserts} total user INSERT statements\n\n";
    
    // Extract all user INSERT statements
    $pattern = '/INSERT INTO `users`[^;]+;/s';
    preg_match_all($pattern, $content, $matches);
    
    echo "3. Extracted " . count($matches[0]) . " INSERT statements\n\n";
    
    $importedCount = 0;
    $skippedCount = 0;
    
    // Process each INSERT statement
    foreach ($matches[0] as $index => $sqlStatement) {
        try {
            echo "Processing INSERT statement " . ($index + 1) . "...\n";
            
            // Execute the SQL statement directly
            $result = \Illuminate\Support\Facades\DB::statement($sqlStatement);
            
            if ($result) {
                $importedCount++;
                echo "   ✓ Successfully imported\n";
            } else {
                $skippedCount++;
                echo "   ✗ Failed to import\n";
            }
            
            if (($index + 1) % 10 == 0) {
                echo "   Processed " . ($index + 1) . " statements...\n";
            }
            
        } catch (Exception $e) {
            $skippedCount++;
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    echo "\n=== User Import Complete ===\n";
    echo "✓ Total INSERT statements found: {$totalInserts}\n";
    echo "✓ Successfully imported: {$importedCount} statements\n";
    echo "✗ Skipped: {$skippedCount} statements\n";
    echo "📊 Total users in database: " . \App\Models\User::count() . "\n";
    
    // Show sample of imported users
    echo "\nSample of imported users:\n";
    $sampleUsers = \App\Models\User::select('name', 'email', 'balance', 'status')->limit(5)->get();
    foreach ($sampleUsers as $user) {
        echo "  - {$user->name} ({$user->email}) - Balance: ₦{$user->balance} - Status: {$user->status}\n";
    }
    
    // Show some statistics
    echo "\nUser Statistics:\n";
    echo "  - Users with balance > 0: " . \App\Models\User::where('balance', '>', 0)->count() . "\n";
    echo "  - Users with balance = 0: " . \App\Models\User::where('balance', '=', 0)->count() . "\n";
    echo "  - Total wallet value: ₦" . \App\Models\User::sum('balance') . "\n";
    
} catch (Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
