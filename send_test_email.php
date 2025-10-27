<?php

/**
 * Test Email Script
 * 
 * This script sends a test welcome email to verify email configuration
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📧 Testing Email Configuration...\n\n";

try {
    // Create a test user object
    $testUser = new stdClass();
    $testUser->name = "Test User";
    $testUser->email = "imax9ja@gmail.com";
    
    // Cast to User model
    $user = \App\Models\User::where('email', 'imax9ja@gmail.com')->first();
    
    if (!$user) {
        echo "Creating test user entry...\n";
        // Just send with the test object
        Mail::to('imax9ja@gmail.com')
            ->send(new WelcomeEmail((object)[
                'name' => 'Test User',
                'email' => 'imax9ja@gmail.com'
            ]));
    } else {
        echo "Sending welcome email to: {$user->email}\n";
        Mail::to($user->email)->send(new WelcomeEmail($user));
    }
    
    echo "\n✅ Test email sent successfully!\n";
    echo "📬 Please check the inbox at: imax9ja@gmail.com\n\n";
    echo "⚠️  NOTE: If the email wasn't delivered, you need to:\n";
    echo "   1. Set up MAIL_PASSWORD in .env with your Gmail App Password\n";
    echo "   2. See EMAIL_SETUP_INSTRUCTIONS.md for detailed steps\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error sending email:\n";
    echo $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'authenticate') !== false || strpos($e->getMessage(), 'Password') !== false) {
        echo "⚠️  This error indicates you need to configure the email password.\n";
        echo "   Follow the instructions in EMAIL_SETUP_INSTRUCTIONS.md\n";
    }
}

