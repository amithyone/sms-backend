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
            // Store V1 user ID for reference
            $table->unsignedBigInteger('v1_user_id')->nullable()->after('id');
            
            // Index for faster lookups
            $table->index('v1_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['v1_user_id']);
            $table->dropColumn(['v1_user_id']);
        });
    }
};
