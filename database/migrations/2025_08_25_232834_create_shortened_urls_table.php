<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortened_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('short_code', 20)->unique();
            $table->text('original_url');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->json('click_data')->nullable(); // Store click analytics
            $table->timestamp('expires_at')->nullable();
            $table->uuid('user_id')->nullable(); // For registered users
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['short_code']);
            $table->index(['user_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortened_urls');
    }
};