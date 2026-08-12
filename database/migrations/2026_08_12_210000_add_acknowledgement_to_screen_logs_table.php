<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives monitoring acknowledgement somewhere honest to live.
 *
 * Before Phase 11, acknowledging an alert wrote `screens.last_heartbeat = now()`
 * and overwrote `screens.status` — so a dead screen looked healthy the moment an
 * administrator clicked a button. Acknowledgement is an administrative fact
 * about an alert, not a device communication, so it is recorded against the log
 * row that raised the alert.
 *
 * Additive only: the original log columns are untouched, so the offline event
 * itself is never destroyed by acknowledging it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screen_logs', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('reported_at');
            $table->foreignId('acknowledged_by')->nullable()->after('acknowledged_at')
                ->constrained('admins')->nullOnDelete();
            $table->string('acknowledgement_note', 500)->nullable()->after('acknowledged_by');

            // Monitoring lists unacknowledged alerts for a screen.
            $table->index(['screen_id', 'acknowledged_at'], 'screen_logs_ack_index');
        });
    }

    public function down(): void
    {
        Schema::table('screen_logs', function (Blueprint $table) {
            $table->dropIndex('screen_logs_ack_index');
            $table->dropConstrainedForeignId('acknowledged_by');
            $table->dropColumn(['acknowledged_at', 'acknowledgement_note']);
        });
    }
};
