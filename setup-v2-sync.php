<?php

/**
 * V2 Sync API Setup Script
 * 
 * This script generates a secure API key for V2 sync and updates your .env file
 */

echo "════════════════════════════════════════════════\n";
echo "   FaddedSMS V2 Sync API Setup\n";
echo "════════════════════════════════════════════════\n\n";

// Generate secure API key
$apiKey = 'v2sync_' . bin2hex(random_bytes(32));

echo "✓ Generated secure API key\n\n";

// Check if .env exists
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "✗ Error: .env file not found\n";
    echo "  Please create .env file first\n";
    exit(1);
}

// Read .env content
$envContent = file_get_contents($envFile);

// Check if V2_SYNC_API_KEY already exists
if (strpos($envContent, 'V2_SYNC_API_KEY=') !== false) {
    echo "⚠ Warning: V2_SYNC_API_KEY already exists in .env\n";
    echo "  Do you want to replace it? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) !== 'y') {
        echo "\n✗ Setup cancelled\n";
        exit(0);
    }
    
    // Replace existing key
    $envContent = preg_replace('/V2_SYNC_API_KEY=.*/', "V2_SYNC_API_KEY={$apiKey}", $envContent);
} else {
    // Add new key
    $envContent .= "\n# V2 Sync API Configuration\n";
    $envContent .= "V2_SYNC_API_KEY={$apiKey}\n";
}

// Write back to .env
file_put_contents($envFile, $envContent);

echo "✓ Updated .env file with API key\n\n";

// Save configuration
$configFile = __DIR__ . '/v2-sync-config.txt';
$configContent = "FaddedSMS V2 Sync API Configuration\n";
$configContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$configContent .= "═══════════════════════════════════════\n";
$configContent .= "V1 Site (This Site) - .env\n";
$configContent .= "═══════════════════════════════════════\n";
$configContent .= "V2_SYNC_API_KEY={$apiKey}\n\n";
$configContent .= "═══════════════════════════════════════\n";
$configContent .= "V2 Site (Old Site) - .env\n";
$configContent .= "═══════════════════════════════════════\n";
$configContent .= "V1_API_URL=https://api.fadsms.com/api/v2-sync\n";
$configContent .= "V1_SYNC_API_KEY={$apiKey}\n\n";
$configContent .= "═══════════════════════════════════════\n";
$configContent .= "IMPORTANT: Keep this API key secure!\n";
$configContent .= "═══════════════════════════════════════\n";

file_put_contents($configFile, $configContent);

echo "✓ Configuration saved to: v2-sync-config.txt\n\n";

echo "════════════════════════════════════════════════\n";
echo "   ✅ Setup Complete!\n";
echo "════════════════════════════════════════════════\n\n";

echo "📋 Next Steps:\n\n";

echo "1. Add to your V2 site's .env file:\n";
echo "   ─────────────────────────────────────────\n";
echo "   V1_API_URL=https://api.fadsms.com/api/v2-sync\n";
echo "   V1_SYNC_API_KEY={$apiKey}\n";
echo "   ─────────────────────────────────────────\n\n";

echo "2. Test the API:\n";
echo "   php test-v2-sync-api.php\n\n";

echo "3. View full documentation:\n";
echo "   cat V2_SYNC_QUICK_SETUP.md\n\n";

echo "⚠ IMPORTANT:\n";
echo "   • Keep the API key secure\n";
echo "   • Configuration saved in: v2-sync-config.txt\n";
echo "   • Do not commit .env to version control\n\n";

echo "════════════════════════════════════════════════\n";

?>

