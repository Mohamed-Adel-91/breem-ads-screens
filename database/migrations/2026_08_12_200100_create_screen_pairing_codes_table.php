<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time, expiring pairing codes an administrator issues for a screen.
 *
 * A device may only claim a screen by presenting a live code — knowing the
 * screen `code` is no longer sufficient. Only the hash is stored; the plaintext
 * is shown once, at generation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();

            // Mirrors screen_id while the code is claimable, nulled once it is
            // consumed or superseded, so the unique index guarantees at most one
            // live code per screen.
            $table->unsignedBigInteger('active_screen_id')->nullable()->unique();

            $table->string('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['screen_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_pairing_codes');
    }
};
