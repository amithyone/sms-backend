<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debugging SQL Format ===\n\n";

try {
    // Read the SQL file
    $sqlFile = 'database/wolrdhome_sms.sql';
    $content = file_get_contents($sqlFile);
    
    if (!$content) {
        throw new Exception("Could not read SQL file: {$sqlFile}");
    }
    
    echo "✓ SQL file loaded successfully\n";
    
    // Count total INSERT statements for users
    $totalInserts = substr_count($content, 'INSERT INTO `users`');
    echo "✓ Found {$totalInserts} total user INSERT statements\n\n";
    
    // Find the first few INSERT statements to examine format
    $lines = explode("\n", $content);
    $foundInserts = 0;
    
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            $foundInserts++;
            echo "=== INSERT Statement #{$foundInserts} (Line {$lineNumber}) ===\n";
            echo "Raw line: " . substr($line, 0, 200) . "...\n";
            
            // Try different regex patterns
            if (preg_match('/VALUES\s*\(([^)]+)\)/', $line, $matches)) {
                echo "✓ VALUES pattern 1 matched: " . substr($matches[1], 0, 100) . "...\n";
            } else {
                echo "✗ VALUES pattern 1 failed\n";
            }
            
            if (preg_match('/VALUES\s*\(([^)]+)\)/s', $line, $matches)) {
                echo "✓ VALUES pattern 2 (multiline) matched: " . substr($matches[1], 0, 100) . "...\n";
            } else {
                echo "✗ VALUES pattern 2 (multiline) failed\n";
            }
            
            if (preg_match('/VALUES\s*\(([^)]+)\)/m', $line, $matches)) {
                echo "✓ VALUES pattern 3 (multiline) matched: " . substr($matches[1], 0, 100) . "...\n";
            } else {
                echo "✗ VALUES pattern 3 (multiline) failed\n";
            }
            
            echo "\n";
            
            if ($foundInserts >= 3) {
                break;
            }
        }
    }
    
    // Also check if there are multi-line INSERT statements
    echo "=== Checking for Multi-line INSERT Statements ===\n";
    $multiLinePattern = '/INSERT INTO `users`[^)]*VALUES\s*\([^)]+\)/s';
    if (preg_match_all($multiLinePattern, $content, $matches)) {
        echo "✓ Found " . count($matches[0]) . " multi-line INSERT statements\n";
        echo "Sample multi-line:\n" . substr($matches[0][0], 0, 300) . "...\n";
    } else {
        echo "✗ No multi-line INSERT statements found\n";
    }
    
} catch (Exception $e) {
    echo "Error during debug: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
