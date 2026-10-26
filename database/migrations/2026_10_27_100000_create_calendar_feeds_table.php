<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The secret addresses calendar apps subscribe to: one per room page and one per person, each
 * kept only as a hash, like kiosk links.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_feeds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_feeds');
    }
};
