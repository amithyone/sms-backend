<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'sms_vat'],
            [
                'value' => '700',
                'type' => 'number',
                'group' => 'pricing',
                'description' => 'Fixed NGN added to SMS price',
                'is_public' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'sms_vat')->delete();
    }
};
