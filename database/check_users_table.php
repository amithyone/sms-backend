<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Users Table Structure ===\n\n";

try {
    // Get the columns in the users table
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
    
    echo "Users table columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column}\n";
    }
    
    echo "\nTotal columns: " . count($columns) . "\n";
    
    // Also check the migration file to see what was created
    echo "\nChecking users migration file...\n";
    $migrationFile = 'database/migrations/2014_10_12_000000_create_users_table.php';
    
    if (file_exists($migrationFile)) {
        echo "✓ Users migration file exists\n";
        $content = file_get_contents($migrationFile);
        
        // Look for Schema::create
        if (preg_match('/Schema::create\s*\(\s*[\'"]users[\'"]\s*,\s*function\s*\(Blueprint\s*\$table\)\s*\{([^}]+)\}/s', $content, $matches)) {
            echo "✓ Found Schema::create for users table\n";
            echo "Migration content:\n";
            echo $matches[1] . "\n";
        } else {
            echo "✗ Could not find Schema::create in migration file\n";
        }
    } else {
        echo "✗ Users migration file not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
