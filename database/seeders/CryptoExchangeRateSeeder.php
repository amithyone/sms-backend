<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CryptoExchangeRate;

class CryptoExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            [
                'payment_method' => 'usdt',
                'rate_per_usd' => 1600.00, // ₦1600 per $1 USDT
                'is_enabled' => true,
                'instructions' => "Send USDT (TRC20) to the wallet address below. After sending, upload a screenshot of your transaction as proof of payment. We will verify and credit your account within 1-24 hours.",
                'disclaimer' => "⚠️ IMPORTANT: We do not accept fraudulent transactions. Any attempt to defraud us will result in account suspension and loss of funds. Only send from wallets you own. We verify all transactions on the blockchain.",
                'admin_wallet_address' => 'TYourUSDTWalletAddressHere',
                'min_amount' => 10.00,
                'max_amount' => 10000.00
            ],
            [
                'payment_method' => 'paypal',
                'rate_per_usd' => 1550.00, // ₦1550 per $1 PayPal
                'is_enabled' => true,
                'instructions' => "Send payment to our PayPal email below. Make sure to include your username in the payment note. Upload a screenshot of your completed transaction. Processing time: 1-24 hours.",
                'disclaimer' => "⚠️ WARNING: PayPal transactions are verified. Chargebacks or fraudulent payments will result in permanent account ban and legal action. We only accept payments from verified PayPal accounts.",
                'admin_paypal_email' => 'payments@fadsms.com',
                'min_amount' => 10.00,
                'max_amount' => 5000.00
            ],
            [
                'payment_method' => 'bitcoin',
                'rate_per_usd' => 1580.00, // ₦1580 per $1 BTC
                'is_enabled' => false,
                'instructions' => "Send Bitcoin to the address below. Upload transaction screenshot with confirmation. Processing time: 3 confirmations required (30-60 minutes).",
                'disclaimer' => "⚠️ SECURITY: We verify all Bitcoin transactions on the blockchain. Fraudulent transactions will be reported to authorities and result in account termination.",
                'admin_wallet_address' => 'YourBTCWalletAddressHere',
                'min_amount' => 20.00,
                'max_amount' => 10000.00
            ],
            [
                'payment_method' => 'ethereum',
                'rate_per_usd' => 1590.00, // ₦1590 per $1 ETH
                'is_enabled' => false,
                'instructions' => "Send Ethereum to the address below. Upload transaction hash screenshot. Processing time: 12 confirmations required (3-5 minutes).",
                'disclaimer' => "⚠️ FRAUD PREVENTION: All Ethereum transactions are verified on-chain. We maintain records of all transactions. Fraudulent activity will result in permanent ban.",
                'admin_wallet_address' => 'YourETHWalletAddressHere',
                'min_amount' => 15.00,
                'max_amount' => 10000.00
            ]
        ];

        foreach ($rates as $rate) {
            CryptoExchangeRate::updateOrCreate(
                ['payment_method' => $rate['payment_method']],
                $rate
            );
        }

        $this->command->info('Crypto exchange rates seeded successfully!');
    }
}
