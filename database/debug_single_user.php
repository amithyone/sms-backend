<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debugging Single User Record ===\n\n";

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
            echo "Line: {$line}\n\n";
            
            // Look for the next few lines to find VALUES
            for ($i = $lineNumber + 1; $i < min($lineNumber + 10, count($lines)); $i++) {
                $nextLine = trim($lines[$i]);
                echo "Line {$i}: {$nextLine}\n";
                
                if (strpos($nextLine, 'VALUES') !== false) {
                    echo "✓ Found VALUES line\n";
                    
                    // Look for the first user record
                    for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
                        $userLine = trim($lines[$j]);
                        echo "Line {$j}: {$userLine}\n";
                        
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
                            
                            if (count($fields) >= 15) {
                                echo "✓ Field count OK\n";
                                
                                $email = trim($fields[2]);
                                $password = trim($fields[3]);
                                $wallet = floatval(trim($fields[4]));
                                $holdWallet = floatval(trim($fields[5]));
                                $username = trim($fields[1]);
                                
                                echo "Username: '{$username}'\n";
                                echo "Email: '{$email}'\n";
                                echo "Password: '{$password}'\n";
                                echo "Wallet: {$wallet}\n";
                                echo "Hold Wallet: {$holdWallet}\n";
                                
                                // Check email validation
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
    echo "Error during debug: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
