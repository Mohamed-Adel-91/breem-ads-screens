<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record who actually creates and approves an advertisement.
 *
 * `ads.created_by` and `ads.approved_by` are foreign keys to `users`, but the
 * dashboard is operated by the `admins` guard — a different table and a different
 * actor domain. Writing an admin id into a users FK would either fail the
 * constraint or, worse, silently attribute the action to whichever unrelated user
 * happens to share that id.
 *
 * This migration is purely additive, so it is safe to run against live data:
 *
 *   - the legacy `created_by` / `approved_by` columns and every value in them are
 *     left exactly as they are;
 *   - the new columns start NULL, including for existing rows. Ownership of a
 *     historical ad cannot be resolved — there is no mapping from a user id to an
 *     admin id — and a null audit value is honest where a fabricated one is not;
 *   - `approved_at` completes the minimum useful trail: who approved, and when.
 *
 * No data is copied, rewritten or backfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->foreignId('created_by_admin_id')
                ->nullable()
                ->after('created_by')
                ->constrained('admins')
                ->nullOnDelete();

            $table->foreignId('approved_by_admin_id')
                ->nullable()
                ->after('approved_by')
                ->constrained('admins')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approved_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
            $table->dropConstrainedForeignId('approved_by_admin_id');
            $table->dropColumn('approved_at');
        });
    }
};
