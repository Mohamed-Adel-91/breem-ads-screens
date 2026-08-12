<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries Phase 14 introduces or relies on. Additive only — no data
 * is read, written or moved.
 *
 * Each one supports a specific query that has no usable index today. The existing
 * composite indexes are `(screen_id, reported_at)` and `(screen_id, played_at)`,
 * which a query that does not filter by screen cannot use, because `screen_id` is
 * the leading column.
 *
 * | Index                        | Query it supports                                              |
 * |------------------------------|----------------------------------------------------------------|
 * | screen_logs.reported_at      | ScreenLog::prunable() — `where reported_at < cutoff`, fleet-wide |
 * | playback_logs.played_at      | PlaybackLog::prunable() — `where played_at < cutoff`, fleet-wide |
 * | playback_logs.(ad_id,played_at) | the playback report's `where ad_id = ?` + period filter, and its `group by ad_id`. `ad_id` had no index at all |
 * | reports.created_at           | the reports index page's `latest('created_at')` with pagination, and Report::prunable() |
 *
 * A fleet-wide prune with no supporting index is a full table scan of the largest
 * tables in the schema, on a schedule — which is exactly the situation to avoid
 * before enabling retention in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screen_logs', function (Blueprint $table) {
            $table->index('reported_at', 'screen_logs_reported_at_index');
        });

        Schema::table('playback_logs', function (Blueprint $table) {
            $table->index('played_at', 'playback_logs_played_at_index');
            $table->index(['ad_id', 'played_at'], 'playback_logs_ad_id_played_at_index');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index('created_at', 'reports_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('screen_logs', function (Blueprint $table) {
            $table->dropIndex('screen_logs_reported_at_index');
        });

        Schema::table('playback_logs', function (Blueprint $table) {
            $table->dropIndex('playback_logs_played_at_index');
            $table->dropIndex('playback_logs_ad_id_played_at_index');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('reports_created_at_index');
        });
    }
};
