<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Create super admin user
    $superAdmin = User::updateOrCreate(
        ['email' => 'admin@fadsms.com'],
        [
            'name' => 'Super Admin',
            'email' => 'admin@fadsms.com',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'status' => 'active',
            'balance' => 0.00,
            'wallet_balance' => 0.00,
            'referral_code' => 'ADMIN001',
        ]
    );

    // Create regular admin user
    $admin = User::updateOrCreate(
        ['email' => 'moderator@fadsms.com'],
        [
            'name' => 'Moderator',
            'email' => 'moderator@fadsms.com',
            'password' => Hash::make('moderator123'),
            'role' => 'admin',
            'status' => 'active',
            'balance' => 0.00,
            'wallet_balance' => 0.00,
            'referral_code' => 'MOD001',
        ]
    );

    echo "✅ Admin users created successfully!\n";
    echo "Super Admin: admin@fadsms.com / admin123\n";
    echo "Moderator: moderator@fadsms.com / moderator123\n";
    echo "Admin panel: http://your-domain.com/admin/login\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
