<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('vtu_access_enabled')->default(true)->after('status');
            $table->string('vtu_access_reason')->nullable()->after('vtu_access_enabled');
            $table->timestamp('vtu_access_disabled_at')->nullable()->after('vtu_access_reason');
            $table->unsignedBigInteger('vtu_access_disabled_by')->nullable()->after('vtu_access_disabled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vtu_access_enabled', 'vtu_access_reason', 'vtu_access_disabled_at', 'vtu_access_disabled_by']);
        });
    }
};
