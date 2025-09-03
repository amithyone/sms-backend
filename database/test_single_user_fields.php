<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Single User Field Parsing ===\n\n";

try {
    // Read the SQL file
    $sqlFile = 'database/wolrdhome_sms.sql';
    $content = file_get_contents($sqlFile);
    
    if (!$content) {
        throw new Exception("Could not read SQL file: {$sqlFile}");
    }
    
    echo "✓ SQL file loaded successfully\n\n";
    
    // Split content into lines and find the first user record
    $lines = explode("\n", $content);
    $foundUser = false;
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        if (strpos($line, 'INSERT INTO `users`') !== false) {
            echo "=== Found INSERT statement at line {$lineNumber} ===\n";
            
            // Look for the next few lines to find VALUES
            for ($i = $lineNumber + 1; $i < min($lineNumber + 10, count($lines)); $i++) {
                $nextLine = trim($lines[$i]);
                
                if (strpos($nextLine, 'VALUES') !== false) {
                    echo "✓ Found VALUES line\n";
                    
                    // Look for the first user record
                    for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
                        $userLine = trim($lines[$j]);
                        
                        if (strpos($userLine, '(') === 0 && strpos($userLine, ')') !== false) {
                            echo "\n=== Found User Record ===\n";
                            echo "Raw line: {$userLine}\n";
                            
                            // Try to parse it
                            $userData = substr($userLine, 1, -1); // Remove ( and )
                            echo "After removing parentheses: {$userData}\n";
                            
                            $userData = str_replace("'", "", $userData);
                            echo "After removing quotes: {$userData}\n";
                            
                            $fields = explode(',', $userData);
                            echo "Number of fields: " . count($fields) . "\n";
                            
                            // Show all fields
                            for ($k = 0; $k < count($fields); $k++) {
                                echo "Field {$k}: '{$fields[$k]}'\n";
                            }
                            
                            if (count($fields) >= 15) {
                                echo "\nExtracted values:\n";
                                echo "Username (index 1): '{$fields[1]}'\n";
                                echo "Email (index 2): '{$fields[2]}'\n";
                                echo "Password (index 3): '{$fields[3]}'\n";
                                echo "Wallet (index 4): '{$fields[4]}'\n";
                                echo "Hold Wallet (index 5): '{$fields[5]}'\n";
                                echo "Role ID (index 6): '{$fields[6]}'\n";
                                echo "Disabled (index 12): '{$fields[12]}'\n";
                                echo "Verify (index 13): '{$fields[13]}'\n";
                                
                                // Check email validation
                                $email = trim($fields[2]);
                                if (empty($email)) {
                                    echo "✗ Email is empty\n";
                                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    echo "✗ Email is invalid: {$email}\n";
                                } else {
                                    echo "✓ Email is valid\n";
                                }
                                
                                $foundUser = true;
                                break 3; // Exit all loops
                            } else {
                                echo "✗ Not enough fields\n";
                            }
                        }
                    }
                }
            }
        }
        
        if ($foundUser) {
            break;
        }
    }
    
} catch (Exception $e) {
    echo "Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
