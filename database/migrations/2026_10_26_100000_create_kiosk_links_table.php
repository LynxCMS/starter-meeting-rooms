<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The secret link each screen opens without signing in: one live link per screen page, kept only
 * as a hash, and never in a page field, so it never reaches a content package or an export.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id')->unique();
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_links');
    }
};
