<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Complete SQL Structure Debug ===\n\n";

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
    
    // Split content into lines and find complete INSERT statements
    $lines = explode("\n", $content);
    $foundInserts = 0;
    
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            $foundInserts++;
            echo "=== INSERT Statement #{$foundInserts} (Line {$lineNumber}) ===\n";
            
            // Get the next few lines to see the complete structure
            $completeStatement = '';
            for ($i = $lineNumber; $i < min($lineNumber + 5, count($lines)); $i++) {
                $completeStatement .= $lines[$i] . "\n";
                if (strpos($lines[$i], ');') !== false) {
                    break; // Found end of statement
                }
            }
            
            echo "Complete statement:\n{$completeStatement}\n";
            
            if ($foundInserts >= 2) {
                break;
            }
        }
    }
    
    // Also try to find the pattern of how many lines each INSERT takes
    echo "=== Analyzing INSERT Statement Length ===\n";
    $insertLines = [];
    $currentInsert = '';
    $lineCount = 0;
    
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            if ($currentInsert !== '') {
                $insertLines[] = $lineCount;
            }
            $currentInsert = $line;
            $lineCount = 1;
        } elseif ($currentInsert !== '') {
            $currentInsert .= $line;
            $lineCount++;
            if (strpos($line, ');') !== false) {
                $insertLines[] = $lineCount;
                $currentInsert = '';
                $lineCount = 0;
            }
        }
    }
    
    if (!empty($insertLines)) {
        echo "INSERT statements use these many lines:\n";
        $lineCounts = array_count_values($insertLines);
        foreach ($lineCounts as $lines => $count) {
            echo "  {$lines} lines: {$count} statements\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error during debug: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
