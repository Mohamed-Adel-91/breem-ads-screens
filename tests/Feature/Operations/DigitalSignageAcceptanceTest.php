<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Jobs\CheckScreenHealthJob;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\Screen;
use App\Models\User;
use App\Notifications\ScreenOfflineNotification;
use App\Services\Screen\HeartbeatService;
use App\Support\ScreenHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * Phase 15 — the formal digital-signage acceptance scenario.
 *
 * One screen's whole life, in order, through the real HTTP surfaces: the admin forms
 * an operator actually fills in, and the signed Device API a player actually calls.
 * Nothing is stubbed and no service is invoked directly where an endpoint exists —
 * the point is to prove the seams hold, because every previous phase tested its own
 * layer and the defects that reached production lived between them.
 *
 * TIME IS DRIVEN, NEVER WAITED ON. Carbon::setTestNow() moves the clock across the
 * schedule start, the schedule end and the offline threshold, so a scenario that
 * spans hours runs in milliseconds and the boundaries land exactly rather than
 * approximately.
 *
 * The numbered steps correspond to the Phase 15 acceptance list.
 */
class DigitalSignageAcceptanceTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    private Admin $operator;
    private User $advertiser;

    /**
     * Every permission the scenario exercises. An operator who could not do one of
     * these could not complete a launch.
     */
    private const PERMISSIONS = [
        'places.view', 'places.create',
        'screens.view', 'screens.create', 'screens.edit',
        'ads.view', 'ads.create', 'ads.edit', 'ads.approve', 'ads.schedule',
        'monitoring.view',
        'reports.view', 'reports.generate',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->operator = Admin::create([
            'first_name' => 'Launch',
            'last_name' => 'Operator',
            'email' => 'acceptance-operator@example.com',
            'password' => 'password',
            'mobile' => '9600000001',
        ]);
        $this->operator->givePermissionTo(self::PERMISSIONS);

        $this->advertiser = User::factory()->create();

        // The shipped production default: no server-side probing, so the operator
        // supplies the video duration. Asserted explicitly in the scenario.
        config(['ads.try_ffprobe' => false]);

        // A launched deployment has an operational recipient. Without one, offline
        // detection still runs but the alert is deliberately dropped, and the
        // scenario would be asserting the unconfigured case instead of the live one.
        config(['notifications.operations.email' => 'ops@example.test']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------- helpers

    private function asOperator(): self
    {
        $this->actingAs($this->operator, 'admin');

        return $this;
    }

    private function playlist(Screen $screen, array $creds): TestResponse
    {
        return $this->deviceGet(
            route('api.v1.screens.playlist', ['screen' => $screen->id]),
            $creds
        );
    }

    /**
     * The ad ids a device would actually be told to play right now.
     *
     * @return array<int, int>
     */
    private function playlistAdIds(Screen $screen, array $creds): array
    {
        $items = $this->playlist($screen, $creds)->json('data.items') ?? [];

        return array_values(array_filter(array_map(
            fn (array $item) => $item['ad_id'] ?? null,
            $items
        )));
    }

    private function heartbeat(array $creds): TestResponse
    {
        return $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds);
    }

    // -------------------------------------------------------------- the scenario

    public function test_the_full_digital_signage_lifecycle(): void
    {
        Notification::fake();

        $day = Carbon::create(2026, 9, 15, 8, 0, 0);
        Carbon::setTestNow($day);

        // ---- 1. Create a Place, through the form an operator uses.
        $this->asOperator()->post(route('admin.places.store', ['lang' => 'en']), [
            'name' => ['en' => 'Riyadh Gallery', 'ar' => 'غاليري الرياض'],
            'address' => ['en' => '1 King Fahd Road'],
            'type' => PlaceType::Other->value,
        ])->assertRedirect();

        $place = Place::sole();
        $this->assertSame('Riyadh Gallery', $place->getTranslation('name', 'en'));

        // ---- 2. Create a Screen in that place. It has never reported, so it starts
        // offline with no heartbeat — `last_heartbeat` is not an accepted input.
        $this->asOperator()->post(route('admin.screens.store', ['lang' => 'en']), [
            'place_id' => $place->id,
            'code' => 'SCR-ACC-01',
            'status' => ScreenStatus::Offline->value,
        ])->assertRedirect();

        $screen = Screen::sole();
        $this->assertNull($screen->last_heartbeat, 'A new screen has never been heard from.');
        $this->assertNull($screen->device_uid);

        // ---- 3. Generate a pairing credential.
        $this->asOperator()
            ->post(route('admin.screens.pairing.generate', ['lang' => 'en', 'screen' => $screen->id]))
            ->assertRedirect()
            ->assertSessionHas('pairing_code');

        $pairingCode = session('pairing_code');
        $this->assertIsString($pairingCode);
        $this->assertNotSame('', $pairingCode);

        // ---- 4. Pair the device. This is the only unauthenticated endpoint, and it
        // is where the device receives its own token and its own signing secret.
        $handshake = $this->postJson('/api/v1/screens/handshake', [
            'code' => 'SCR-ACC-01',
            'pairing_code' => $pairingCode,
            'device' => ['uid' => 'acceptance-player-01', 'model' => 'BX-1'],
        ]);

        $handshake->assertCreated();
        $handshake->assertJsonPath('data.auth.token_type', 'Bearer');

        $creds = [
            'token' => $handshake->json('data.auth.access_token'),
            'secret' => $handshake->json('data.auth.hmac_secret'),
        ];

        $this->assertNotEmpty($creds['token']);
        $this->assertNotEmpty($creds['secret']);
        $this->assertNotSame($creds['token'], $creds['secret'], 'The token is not the signing key.');

        // The credentials are per device: the screen's own uid is not the token.
        $screen->refresh();
        $this->assertSame('acceptance-player-01', $screen->device_uid);
        $this->assertNotSame($screen->device_uid, $creds['token']);

        // ---- 5/6. An authenticated heartbeat, and the screen is online.
        $this->heartbeat($creds)->assertOk();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertTrue($screen->last_heartbeat->equalTo($day), 'Server receipt time, not client claim.');

        // The device config endpoint answers for a paired device.
        $this->deviceGet(route('api.v1.config.show'), $creds)->assertOk();

        // ---- 7. Upload an Ad. A video with no duration is refused (Phase 15), so the
        // operator supplies one; the ad starts `pending` whatever the form says.
        $this->asOperator()->post(route('admin.ads.store', ['lang' => 'en']), [
            'title' => ['en' => 'Autumn Campaign'],
            'created_by' => $this->advertiser->id,
            'creative' => UploadedFile::fake()->create('autumn.mp4', 256, 'video/mp4'),
            'duration_seconds' => 20,
            'status' => AdStatus::Active->value,
        ])->assertRedirect();

        $ad = Ad::sole();
        $this->assertSame(AdStatus::Pending, $ad->status, 'Publishing is an approval, never a form field.');
        $this->assertSame(20, (int) $ad->duration_seconds);

        // A pending ad reaches no screen even before assignment or scheduling.
        $this->assertSame([], $this->playlistAdIds($screen, $creds));

        // ---- 8. Approve, then ---- 9. publish. Two distinct decisions.
        $this->asOperator()->post(
            route('admin.ads.transition', ['lang' => 'en', 'ad' => $ad->id]),
            ['action' => AdStatus::ACTION_APPROVE]
        )->assertRedirect();

        $this->assertSame(AdStatus::Approved, $ad->fresh()->status);
        $this->assertNotNull($ad->fresh()->approved_at);
        $this->assertSame($this->operator->id, $ad->fresh()->approved_by_admin_id);

        $this->asOperator()->post(
            route('admin.ads.transition', ['lang' => 'en', 'ad' => $ad->id]),
            ['action' => AdStatus::ACTION_PUBLISH]
        )->assertRedirect();

        $this->assertSame(AdStatus::Active, $ad->fresh()->status);

        // ---- 10. Assign to the screen.
        $this->asOperator()->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), [
            'title' => $ad->getTranslations('title'),
            'created_by' => $ad->created_by,
            'duration_seconds' => $ad->duration_seconds,
            'screens' => [$screen->id],
            'play_order' => [$screen->id => 1],
        ])->assertRedirect();

        $ad->refresh();
        $this->assertTrue($ad->screens->contains($screen->id));
        $this->assertSame(AdStatus::Active, $ad->status, 'Assignment must not revoke approval.');

        // ---- 11. Configure a schedule: a window later today.
        $windowStart = $day->copy()->setTime(12, 0);
        $windowEnd = $day->copy()->setTime(14, 0);

        $this->asOperator()->post(
            route('admin.ads.schedules.store', ['lang' => 'en', 'ad' => $ad->id]),
            [
                'screen_id' => $screen->id,
                'start_time' => $windowStart->toDateTimeString(),
                'end_time' => $windowEnd->toDateTimeString(),
                'is_active' => true,
            ]
        )->assertRedirect();

        // ---- 12. Before the window opens, the ad is not in the playlist.
        $this->assertSame(
            [],
            $this->playlistAdIds($screen, $creds),
            'A scheduled ad must not play before its window opens.'
        );

        // ---- 13/14/15. Cross the start boundary; the ad appears.
        Carbon::setTestNow($windowStart->copy()->addMinute());
        $this->heartbeat($creds)->assertOk();

        $this->assertSame(
            [$ad->id],
            $this->playlistAdIds($screen, $creds),
            'Inside its window the ad must reach the device.'
        );

        $item = collect($this->playlist($screen, $creds)->json('data.items'))
            ->firstWhere('ad_id', $ad->id);

        $this->assertSame(20, $item['duration_seconds'], 'The device is told a playable duration.');
        $this->assertSame('video', $item['file_type']);
        $this->assertStringStartsWith('http', (string) $item['file_url'], 'Creative URLs are absolute.');
        $this->assertStringNotContainsString(
            $this->uploadPath(),
            (string) $item['file_url'],
            'No local filesystem path may leak into the API.'
        );

        // ---- 16/17. The device reports playback; a PlaybackLog exists.
        $playedAt = Carbon::now();

        $this->devicePost('/api/v1/playbacks', [
            'entries' => [[
                'ad_id' => $ad->id,
                'played_at' => $playedAt->toIso8601String(),
                'duration' => 20,
            ]],
        ], $creds)->assertAccepted();

        $log = PlaybackLog::sole();
        $this->assertSame($ad->id, (int) $log->ad_id);
        $this->assertSame($screen->id, (int) $log->screen_id, 'Playback is attributed to the authenticated screen.');
        $this->assertSame(20, (int) $log->duration);

        // ---- 18/19. Cross the end boundary; the ad stops being served.
        Carbon::setTestNow($windowEnd->copy()->addMinute());
        $this->heartbeat($creds)->assertOk();

        $afterWindow = $this->playlist($screen, $creds);
        $afterWindow->assertOk();

        $this->assertSame(
            [],
            $this->playlistAdIds($screen, $creds),
            'Past its window the ad must disappear, and the playlist must still answer.'
        );

        // With nothing eligible the response is a well-formed empty playlist — the
        // device is never handed a broken payload.
        $this->assertIsArray($afterWindow->json('data.items'));

        // ---- 20/21/22/23. Silence, then the sweep. The threshold is configuration,
        // so the test moves past it rather than assuming a number.
        Carbon::setTestNow(
            $windowEnd->copy()->addMinute()->addSeconds(ScreenHealth::offlineAfter() + 60)
        );

        (new CheckScreenHealthJob())->handle(app(HeartbeatService::class));

        $screen->refresh();
        $this->assertSame(ScreenStatus::Offline, $screen->status, 'A silent screen must be detected, not assumed live.');
        $this->assertSame(ScreenStatus::Offline, $screen->logs()->latest('id')->first()->status);

        // ---- 24. The transition raised an operational alert.
        Notification::assertSentOnDemand(ScreenOfflineNotification::class);

        // ---- 25/26. The device comes back on its own; no admin action needed.
        $recovery = Carbon::now()->addMinutes(5);
        Carbon::setTestNow($recovery);

        $this->heartbeat($creds)->assertOk();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertTrue($screen->last_heartbeat->equalTo($recovery));

        // ---- 27. Monitoring tells the truth about the screen it just watched fail.
        $this->asOperator()
            ->get(route('admin.monitoring.index', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('SCR-ACC-01');

        $this->asOperator()
            ->get(route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id]))
            ->assertOk()
            ->assertSee('SCR-ACC-01');

        // ---- 28. A playback report over the day that just happened.
        $this->asOperator()->post(route('admin.reports.generate', ['lang' => 'en']), [
            'name' => 'Acceptance Playback',
            'type' => 'playback',
            'from_date' => $day->toDateString(),
            'to_date' => $day->toDateString(),
        ])->assertSessionHasNoErrors();

        $playback = Report::where('type', 'playback')->sole();
        $rows = $playback->data['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame($ad->id, $rows[0]['ad_id']);
        $this->assertSame(1, $rows[0]['plays']);
        $this->assertSame(20, $rows[0]['total_duration']);
        $this->assertSame(['SCR-ACC-01'], $rows[0]['screens']);

        // ---- 29. An availability report over the same day.
        $this->asOperator()->post(route('admin.reports.generate', ['lang' => 'en']), [
            'name' => 'Acceptance Availability',
            'type' => 'screen-uptime',
            'from_date' => $day->toDateString(),
            'to_date' => $day->toDateString(),
        ])->assertSessionHasNoErrors();

        $uptime = Report::where('type', 'screen-uptime')->sole();
        $uptimeRows = $uptime->data['rows'];

        $this->assertCount(1, $uptimeRows);
        $this->assertSame('SCR-ACC-01', $uptimeRows[0]['screen_code']);
        $this->assertNotNull(
            $uptimeRows[0]['availability'],
            'The screen reported and failed inside the window, so availability is measurable.'
        );
        $this->assertGreaterThan(
            0,
            $uptimeRows[0]['offline_seconds'],
            'The outage this scenario caused must be visible in the availability figures.'
        );

        // ---- 30. The snapshot survives export, and keeps its figures for ever.
        $this->assertSame(
            \App\Services\Reports\ReportGenerationService::SCHEMA_VERSION,
            $playback->data['schema_version']
        );

        $export = $this->asOperator()->get(
            route('admin.reports.download', ['lang' => 'en', 'report' => $playback->id])
        );

        $export->assertOk();
        $csv = $export->streamedContent();

        $this->assertStringContainsString('Ad ID', $csv);
        $this->assertStringContainsString('Autumn Campaign', $csv);
        $this->assertStringContainsString('SCR-ACC-01', $csv);

        // The report is a snapshot, not a live query: pruning the source log leaves
        // the figures intact.
        PlaybackLog::query()->delete();

        $this->assertSame(
            1,
            Report::where('type', 'playback')->sole()->data['rows'][0]['plays'],
            'A stored report must keep reading correctly after its source logs are gone.'
        );
    }

    // ------------------------------------------------- credential revocation path

    public function test_revoking_a_device_stops_it_dead_and_re_pairing_restores_it(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 20, 9, 0, 0));

        $place = Place::factory()->create();
        $screen = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-ACC-02',
            'status' => ScreenStatus::Offline->value,
        ]);

        $creds = $this->pairScreen($screen, 'acceptance-player-02');
        $this->heartbeat($creds)->assertOk();

        // An administrator revokes the device — a lost or stolen player.
        $this->asOperator()
            ->delete(route('admin.screens.pairing.reset', ['lang' => 'en', 'screen' => $screen->id]))
            ->assertRedirect();

        $this->heartbeat($creds)->assertUnauthorized();
        $this->playlist($screen->fresh(), $creds)->assertUnauthorized();

        // Re-pairing issues genuinely new material, not the old secret again.
        $this->asOperator()
            ->post(route('admin.screens.pairing.generate', ['lang' => 'en', 'screen' => $screen->fresh()->id]))
            ->assertRedirect();

        $replacement = $this->postJson('/api/v1/screens/handshake', [
            'code' => 'SCR-ACC-02',
            'pairing_code' => session('pairing_code'),
            'device' => ['uid' => 'acceptance-player-02-replacement'],
        ]);

        $replacement->assertCreated();

        $newCreds = [
            'token' => $replacement->json('data.auth.access_token'),
            'secret' => $replacement->json('data.auth.hmac_secret'),
        ];

        $this->assertNotSame($creds['token'], $newCreds['token']);
        $this->assertNotSame($creds['secret'], $newCreds['secret']);

        $this->heartbeat($newCreds)->assertOk();
        $this->heartbeat($creds)->assertUnauthorized();
    }
}
