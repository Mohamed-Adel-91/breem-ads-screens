<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replay protection: one row per (credential, nonce) actually used.
 *
 * A database unique constraint is deliberately used instead of the cache. The
 * cache store is configuration-driven (`CACHE_STORE`), and not every driver
 * implements an atomic add() — the file store falls back to a non-atomic
 * get-then-put. Replay protection must not be silently weakened by an
 * environment change, so the guarantee lives in the schema.
 *
 * Rows are pruned once they fall outside the signature leeway window; see
 * App\Services\Screen\DeviceReplayGuard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_request_nonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')
                ->constrained('screen_device_credentials')
                ->cascadeOnDelete();
            $table->string('nonce', 128);
            $table->timestamp('used_at');

            // The security invariant.
            $table->unique(['credential_id', 'nonce']);
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_request_nonces');
    }
};
