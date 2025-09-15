<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE sms_services MODIFY COLUMN provider ENUM('5sim','dassy','tiger_sms','textverified','smspool') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sms_services MODIFY COLUMN provider ENUM('5sim','dassy','tiger_sms','textverified') NOT NULL");
    }
};



