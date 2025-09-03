<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsServiceCodeSeeder extends Seeder
{
    public function run(): void
    {
        $provider = 'tiger_sms';
        $pairs = [
            // Curated list from user
            ['code' => 'yo', 'name' => 'Amazon'],
            ['code' => 'aaq', 'name' => 'Netflix'],
            ['code' => 'nc', 'name' => 'PayPal'],
            ['code' => 'xz', 'name' => 'Payoneer'],
            ['code' => 'tr', 'name' => 'Paytm'],
            ['code' => 'ab', 'name' => 'AliExpress'],
            ['code' => 'lp', 'name' => 'Alibaba'],
            ['code' => 'hx', 'name' => 'Alipay/Alibaba/1688'],
            ['code' => 'aky', 'name' => 'Google / YouTube / Gmail'],
            ['code' => 'rm', 'name' => 'Facebook'],
            ['code' => 'inp', 'name' => 'Instagram + Threads'],
            ['code' => 'wm', 'name' => 'Snapchat'],
            ['code' => 'hb', 'name' => 'Twitter (X)'],
            ['code' => 'aki', 'name' => 'TikTok / Douyin'],
            ['code' => 'lf', 'name' => 'Tinder'],
            ['code' => 'qu', 'name' => 'Airbnb'],
            ['code' => 'uk', 'name' => 'Airtel'],
            ['code' => 'vg', 'name' => 'Shopee'],
            ['code' => 'sb', 'name' => 'Lazada'],
            ['code' => 'eh', 'name' => 'Temu'],
            ['code' => 'sa', 'name' => 'Agoda'],
            ['code' => 'acl', 'name' => 'Tokopedia'],
            ['code' => 'abj', 'name' => 'Trendyol'],
            ['code' => 'dg', 'name' => 'Meta'],
            ['code' => 'mc', 'name' => 'Microsoft'],
            ['code' => 'ml', 'name' => 'Apple'],
            ['code' => 'ub', 'name' => 'Ubisoft'],
            ['code' => 'aho', 'name' => 'Uber'],
            ['code' => 'ajr', 'name' => 'Bolt'],
            ['code' => 'abl', 'name' => 'Grab'],
            ['code' => 'bp', 'name' => 'Gojek'],
            ['code' => 'cd', 'name' => 'Spotify'],
            ['code' => 'stc', 'name' => 'Steam'],
            ['code' => 'ee', 'name' => 'Twitch'],
            ['code' => 'ij', 'name' => 'Robinhood'],
            ['code' => 'aln', 'name' => 'Revolut'],
            ['code' => 'qt', 'name' => 'Monobank'],
            ['code' => 'zy', 'name' => 'Nubank'],
            ['code' => 'act', 'name' => 'Maybank'],
            ['code' => 'uw', 'name' => 'Klarna'],
            ['code' => 'ff', 'name' => 'AWS'],
            ['code' => 'wa', 'name' => 'WhatsApp'],
            // Extra common Tiger codes included previously
            ['code' => 'ajh', 'name' => 'WAUG'],
            ['code' => 'abo', 'name' => 'WEBDE'],
            ['code' => 'alf', 'name' => 'WEBULL'],
        ];

        foreach (['tiger_sms','5sim'] as $prov) {
            foreach ($pairs as $row) {
                DB::table('sms_service_codes')->updateOrInsert(
                    ['provider' => $prov, 'code' => $row['code']],
                    ['name' => $row['name'], 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}


