<?php

namespace Tests\Feature\Admin;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Every screen gets its own latest log.
 *
 * Monitoring used to eager-load `'logs' => fn ($q) => $q->latest()->limit(1)`,
 * which prior audits recorded as a bug: eager loading issues one child query for
 * the whole page, so `LIMIT 1` would return a single row overall and only one
 * screen in the list would show a last report.
 *
 * That was measured on this codebase during Phase 11 and found NOT to reproduce.
 * Laravel 11+ rewrites such an eager-load limit into
 * `row_number() OVER (PARTITION BY screen_id ORDER BY reported_at DESC)`, so
 * every screen already received its own row. The finding was accurate for older
 * Laravel and is now obsolete.
 *
 * Screen::latestLog() replaces it anyway — the intent belongs in the model, not
 * in a query constraint that only works because the framework rewrites it — and
 * these tests exist so the guarantee is asserted rather than assumed, whichever
 * mechanism provides it.
 */
class MonitoringLatestLogTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('monitoring.view', 'admin');

        $this->admin = Admin::create([
            'first_name' => 'Latest',
            'last_name' => 'Log',
            'email' => 'latest-log@example.com',
            'password' => 'password',
            'mobile' => '7100000001',
        ]);
        $this->admin->givePermissionTo('monitoring.view');
    }

    /**
     * @return array<int, Screen>
     */
    private function makeFleet(int $count): array
    {
        $place = Place::create([
            'name' => ['en' => 'Fleet Hall'],
            'address' => ['en' => '4 Fleet Street'],
            'type' => PlaceType::Other,
        ]);

        $screens = [];

        for ($i = 1; $i <= $count; $i++) {
            $screen = Screen::create([
                'place_id' => $place->id,
                'code' => sprintf('SCR-FLEET-%02d', $i),
                'device_uid' => sprintf('uid-fleet-%02d', $i),
                'status' => ScreenStatus::Online,
                'last_heartbeat' => now(),
            ]);

            // An older entry and a newer one, so "latest" is a real choice.
            $screen->logs()->create([
                'status' => ScreenStatus::Offline->value,
                'reported_at' => now()->subHours(5),
                'current_ad_code' => 'OLD-'.$i,
            ]);
            $screen->logs()->create([
                'status' => ScreenStatus::Online->value,
                'reported_at' => now()->subMinutes($i),
                'current_ad_code' => 'NEW-'.$i,
            ]);

            $screens[] = $screen;
        }

        return $screens;
    }

    public function test_every_screen_receives_its_own_latest_log(): void
    {
        // Ten screens: comfortably more than the single row the old LIMIT 1
        // could ever have produced.
        $screens = $this->makeFleet(10);

        $loaded = Screen::with('latestLog')->orderBy('code')->get();

        $this->assertCount(10, $loaded);

        foreach ($loaded as $index => $screen) {
            $this->assertNotNull(
                $screen->latestLog,
                "Screen {$screen->code} has logs but received no latest log."
            );
            $this->assertSame('NEW-'.($index + 1), $screen->latestLog->current_ad_code);
            $this->assertSame(ScreenStatus::Online, $screen->latestLog->status);
        }

        // Guard the count explicitly.
        $this->assertSame(
            10,
            $loaded->filter(fn ($screen) => $screen->latestLog !== null)->count(),
            'Every screen with history must have a latest log.'
        );

        $this->assertCount(10, $screens);
    }

    public function test_a_screen_without_logs_has_no_latest_log(): void
    {
        $this->makeFleet(2);

        $place = Place::first();
        $silent = Screen::create([
            'place_id' => $place->id,
            'code' => 'SCR-SILENT',
            'device_uid' => 'uid-silent',
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => null,
        ]);

        $loaded = Screen::with('latestLog')->findOrFail($silent->id);

        $this->assertNull($loaded->latestLog);
    }

    public function test_the_monitoring_index_does_not_n_plus_one_over_logs(): void
    {
        $this->makeFleet(12);

        DB::enableQueryLog();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.index', ['lang' => 'en']));

        $response->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $logQueries = collect($queries)
            ->filter(fn ($query) => str_contains($query['query'], 'screen_logs'))
            ->count();

        // Eager loading plus the two withCount aggregates — a bounded number,
        // and nowhere near one query per screen.
        $this->assertLessThan(
            12,
            $logQueries,
            'The monitoring index is querying screen_logs once per screen.'
        );
    }

    public function test_the_monitoring_index_renders_a_latest_report_for_every_screen(): void
    {
        $this->makeFleet(5);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.index', ['lang' => 'en']));

        $response->assertOk();

        $body = $response->getContent();
        $noLogsLabel = __('admin.monitoring.table.no_logs');

        $this->assertSame(
            0,
            substr_count($body, $noLogsLabel),
            'Screens with history must not render the "no logs" placeholder.'
        );
    }
}
