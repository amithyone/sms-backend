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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('button_text')->default('Learn More');
            $table->string('button_url');
            $table->string('background_type')->default('color'); // 'color' or 'image'
            $table->string('background_color')->default('#3B82F6'); // Default blue
            $table->string('background_image')->nullable();
            $table->string('text_color')->default('#FFFFFF'); // Default white text
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // For featured ads like fadded.net
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};