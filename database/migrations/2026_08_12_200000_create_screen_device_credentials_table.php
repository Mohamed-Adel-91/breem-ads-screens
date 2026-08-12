<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-device credentials issued when a screen is paired.
 *
 * Additive: `screens` is not modified and existing rows are untouched. Screens
 * already carrying a `device_uid` simply have no credential until an
 * administrator pairs them, which is the intended migration path — no plaintext
 * token or secret can be invented for a device that could never receive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_device_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();

            // Mirrors screen_id while the credential is active and is nulled on
            // revocation. The unique index then enforces "at most one active
            // credential per screen" in the database rather than in PHP.
            $table->unsignedBigInteger('active_screen_id')->nullable()->unique();

            // Physical device identity. Metadata only — never authentication.
            $table->string('device_uid')->nullable();

            // SHA-256 of the bearer token. The plaintext is returned once and
            // never stored.
            $table->string('token_hash', 64)->unique();

            // Per-device signing secret, encrypted at rest.
            $table->text('hmac_secret');

            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['screen_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_device_credentials');
    }
};
