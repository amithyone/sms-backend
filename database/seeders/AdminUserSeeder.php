<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        User::updateOrCreate(
            ['email' => 'admin@fadsms.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@fadsms.com',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'status' => 'active',
                'balance' => 0.00,
                'referral_code' => 'ADMIN001',
            ]
        );

        // Create regular admin user
        User::updateOrCreate(
            ['email' => 'moderator@fadsms.com'],
            [
                'name' => 'Moderator',
                'email' => 'moderator@fadsms.com',
                'password' => Hash::make('moderator123'),
                'role' => 'admin',
                'status' => 'active',
                'balance' => 0.00,
                'referral_code' => 'MOD001',
            ]
        );

        $this->command->info('Admin users created successfully!');
        $this->command->info('Super Admin: admin@fadsms.com / admin123');
        $this->command->info('Moderator: moderator@fadsms.com / moderator123');
    }
}
