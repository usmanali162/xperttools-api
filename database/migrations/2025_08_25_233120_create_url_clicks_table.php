<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('url_clicks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shortened_url_id');
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->timestamp('clicked_at');
            
            $table->foreign('shortened_url_id')->references('id')->on('shortened_urls')->onDelete('cascade');
            $table->index(['shortened_url_id']);
            $table->index(['clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_clicks');
    }
};