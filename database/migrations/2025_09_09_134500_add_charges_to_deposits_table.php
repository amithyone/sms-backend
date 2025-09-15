<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->decimal('charges', 10, 2)->nullable()->after('amount');
            $table->decimal('actual_amount', 10, 2)->nullable()->after('charges');
            $table->decimal('credit_amount', 10, 2)->nullable()->after('actual_amount');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['charges', 'actual_amount', 'credit_amount']);
        });
    }
};
