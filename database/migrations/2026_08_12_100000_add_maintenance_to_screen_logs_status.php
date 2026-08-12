<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns `screen_logs.status` with the authoritative Screen status domain.
 *
 * `App\Enums\ScreenStatus` declares online / offline / maintenance, and
 * `screens.status` already stores all three. `screen_logs.status`, however, was
 * created as enum('online','offline'), so every writer that legitimately reports
 * `maintenance` — MonitoringController::acknowledgeAlert() and
 * HeartbeatService::heartbeat() — fails with an integrity-constraint violation.
 *
 * This migration only widens the allowed value set. No row is rewritten, no data
 * is truncated, and the column keeps its type, nullability and default.
 */
return new class extends Migration
{
    private const WIDENED = ['online', 'offline', 'maintenance'];
    private const ORIGINAL = ['online', 'offline'];

    public function up(): void
    {
        $this->applyStatusValues(self::WIDENED);
    }

    public function down(): void
    {
        // Rows already stored as `maintenance` cannot satisfy the narrower set.
        // They are mapped to `offline` — the closest non-serving state — so the
        // rollback never deletes log history.
        DB::table('screen_logs')
            ->where('status', 'maintenance')
            ->update(['status' => 'offline']);

        $this->applyStatusValues(self::ORIGINAL);
    }

    /**
     * Rewrite the column's allowed value set in a platform-appropriate way.
     *
     * @param  array<int, string>  $values
     */
    private function applyStatusValues(array $values): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // In-place ALTER: existing rows are preserved untouched.
            $list = collect($values)->map(fn (string $value) => "'" . $value . "'")->implode(', ');

            $connection->statement(
                "ALTER TABLE `screen_logs` MODIFY `status` ENUM({$list}) NOT NULL DEFAULT 'online'"
            );

            return;
        }

        // SQLite (test suite), PostgreSQL and SQL Server all express an enum as a
        // string plus a CHECK constraint. Laravel's native change() rebuilds the
        // constraint while copying every existing row across.
        Schema::table('screen_logs', function (Blueprint $table) use ($values) {
            $table->enum('status', $values)->default('online')->change();
        });
    }
};
