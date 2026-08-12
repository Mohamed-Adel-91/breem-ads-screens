<?php

namespace Database\Seeders;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use App\Services\Reports\ReportGenerationService;
use App\Support\ReportType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ReportsAndLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = $this->ensureAdmin();
        $user = $this->ensureUser();
        $screen = $this->ensureScreenWithPlace();
        $ad = $this->ensureAd($user, $screen);

        $reports = $this->seedReports($admin);
        $playbackLogs = $this->seedPlaybackLogs($screen, $ad);
        $screenLogs = $this->seedScreenLogs($screen);

        $this->seedActivityLog($admin, $screen, $reports, $screenLogs, $playbackLogs);
    }

    private function ensureAdmin(): Admin
    {
        return Admin::first() ?? Admin::create([
            'first_name' => 'Seed',
            'last_name' => 'Admin',
            'email' => 'seed-admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function ensureUser(): User
    {
        return User::first() ?? User::create([
            'name' => 'Seed Advertiser',
            'email' => 'seed.advertiser@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function ensureScreenWithPlace(): Screen
    {
        $place = Place::firstOrCreate(
            ['name->en' => 'Insights Plaza'],
            [
                'name' => ['en' => 'Insights Plaza'],
                'address' => ['en' => '42 Analytics Lane, Seed City'],
                'type' => PlaceType::Other,
            ]
        );

        return Screen::firstOrCreate(
            ['code' => 'SCR-INSIGHTS-001'],
            [
                'place_id' => $place->id,
                'device_uid' => 'seed-screen-001',
                'status' => ScreenStatus::Online,
                'last_heartbeat' => Carbon::now()->subMinutes(5),
            ]
        );
    }

    private function ensureAd(User $user, Screen $screen): Ad
    {
        $ad = Ad::firstOrCreate(
            ['file_path' => 'upload/ads/metrics-loop.mp4'],
            [
                'title' => ['en' => 'Metrics Loop'],
                'description' => ['en' => 'Demo clip used for seeding playback statistics.'],
                'file_type' => 'video',
                'duration_seconds' => 45,
                'status' => AdStatus::Active,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(14),
            ]
        );

        $ad->screens()->syncWithoutDetaching([
            $screen->id => ['play_order' => 1],
        ]);

        return $ad;
    }

    /**
     * @return array<string, Report>
     */
    private function seedReports(Admin $admin): array
    {
        $today = Carbon::today();

        $payloads = [
            'daily_playback' => [
                'name' => 'Daily Playback Overview',
                'type' => ReportType::PLAYBACK,
                // Filter keys match GenerateReportRequest, so a seeded report's stored
                // filters describe a period the generator could actually reproduce.
                'filters' => [
                    'from_date' => $today->copy()->subDay()->toDateString(),
                    'to_date' => $today->toDateString(),
                ],
                'data' => [
                    'schema_version' => ReportGenerationService::SCHEMA_VERSION,
                    'rows' => [
                        ['ad_id' => null, 'ad_title' => 'Metrics Loop', 'plays' => 156, 'total_duration' => 4992, 'screens' => ['SCR-INSIGHTS-001']],
                    ],
                    'summary' => ['advertisements' => 1, 'plays' => 156, 'total_duration' => 4992],
                    'total_logs' => 156,
                ],
            ],
            // Types below were `performance` and `availability`, neither of which
            // GenerateReportRequest accepted or the generator could produce — a seeded
            // report stored under a type nothing could make. They now use the
            // canonical values from App\Support\ReportType, which mean the same
            // things, and their payloads use the `rows` shape the show page and CSV
            // export actually read. Existing rows carrying the old values are left
            // alone and still render, via ReportType::canonical().
            'top_ads' => [
                'name' => 'Top Performing Ads',
                'type' => ReportType::PLAYBACK,
                'filters' => [
                    'from_date' => $today->copy()->subDays(7)->toDateString(),
                    'to_date' => $today->toDateString(),
                ],
                'data' => [
                    'schema_version' => ReportGenerationService::SCHEMA_VERSION,
                    'rows' => [
                        ['ad_id' => null, 'ad_title' => 'Metrics Loop', 'plays' => 92, 'total_duration' => 2760, 'screens' => ['SCR-INSIGHTS-001']],
                        ['ad_id' => null, 'ad_title' => 'City Snapshot', 'plays' => 58, 'total_duration' => 1740, 'screens' => ['SCR-INSIGHTS-001']],
                        ['ad_id' => null, 'ad_title' => 'Promo Minute', 'plays' => 41, 'total_duration' => 1230, 'screens' => ['SCR-INSIGHTS-001']],
                    ],
                    'summary' => ['advertisements' => 3, 'plays' => 191, 'total_duration' => 5730],
                    'total_logs' => 191,
                ],
            ],
            'screen_uptime' => [
                'name' => 'Screen Availability Snapshot',
                'type' => ReportType::SCREEN_UPTIME,
                'filters' => [
                    'from_date' => $today->copy()->subDays(7)->toDateString(),
                    'to_date' => $today->toDateString(),
                ],
                'data' => [
                    'schema_version' => ReportGenerationService::SCHEMA_VERSION,
                    'rows' => [
                        [
                            'screen_id' => null,
                            'screen_code' => 'SCR-INSIGHTS-001',
                            'place' => 'Insights Plaza',
                            'availability' => 99.2,
                            'online_seconds' => 600_048,
                            'offline_seconds' => 4_752,
                            'maintenance_seconds' => 0,
                            'unknown_seconds' => 0,
                            'measured_seconds' => 604_800,
                        ],
                    ],
                    'summary' => [
                        'screens' => 1,
                        'measured_screens' => 1,
                        'average_availability' => 99.2,
                        'offline_seconds' => 4_752,
                    ],
                ],
            ],
        ];

        $reports = [];

        foreach ($payloads as $key => $payload) {
            $reports[$key] = Report::updateOrCreate(
                ['name' => $payload['name'], 'type' => $payload['type']],
                [
                    'filters' => $payload['filters'],
                    'data' => $payload['data'],
                    'generated_by' => $admin->id,
                ]
            );
        }

        return $reports;
    }

    /**
     * @return array<int, PlaybackLog>
     */
    private function seedPlaybackLogs(Screen $screen, Ad $ad): array
    {
        $now = Carbon::now();

        $entries = [
            [
                'played_at' => $now->copy()->subHours(6),
                'duration' => 30,
                'extra' => [
                    'app_version' => '2.1.0',
                    'battery' => 87,
                    'storage_free_mb' => 6092,
                ],
            ],
            [
                'played_at' => $now->copy()->subHours(3),
                'duration' => 45,
                'extra' => [
                    'app_version' => '2.1.0',
                    'battery' => 74,
                    'storage_free_mb' => 5821,
                ],
            ],
            [
                'played_at' => $now->copy()->subHour(),
                'duration' => 30,
                'extra' => [
                    'app_version' => '2.1.0',
                    'battery' => 69,
                    'storage_free_mb' => 5710,
                ],
            ],
        ];

        $logs = [];

        foreach ($entries as $entry) {
            $logs[] = PlaybackLog::updateOrCreate(
                [
                    'screen_id' => $screen->id,
                    'ad_id' => $ad->id,
                    'played_at' => $entry['played_at'],
                ],
                [
                    'duration' => $entry['duration'],
                    'extra' => $entry['extra'],
                    'created_at' => $entry['played_at'],
                    'updated_at' => $entry['played_at'],
                ]
            );
        }

        return $logs;
    }

    /**
     * @return array<int, ScreenLog>
     */
    private function seedScreenLogs(Screen $screen): array
    {
        $now = Carbon::now();

        $entries = [
            [
                'reported_at' => $now->copy()->subHours(8),
                'status' => ScreenStatus::Online,
                'current_ad_code' => 'AD-METRIC-001',
            ],
            [
                'reported_at' => $now->copy()->subHours(2),
                'status' => ScreenStatus::Offline,
                'current_ad_code' => 'AD-METRIC-001',
            ],
            [
                'reported_at' => $now->copy()->subMinutes(25),
                'status' => ScreenStatus::Online,
                'current_ad_code' => 'AD-METRIC-002',
            ],
        ];

        $logs = [];

        foreach ($entries as $entry) {
            $logs[] = ScreenLog::updateOrCreate(
                [
                    'screen_id' => $screen->id,
                    'reported_at' => $entry['reported_at'],
                ],
                [
                    'current_ad_code' => $entry['current_ad_code'],
                    'status' => $entry['status'],
                    'created_at' => $entry['reported_at'],
                    'updated_at' => $entry['reported_at'],
                ]
            );
        }

        return $logs;
    }

    /**
     * @param array<string, Report> $reports
     * @param array<int, ScreenLog> $screenLogs
     * @param array<int, PlaybackLog> $playbackLogs
     */
    private function seedActivityLog(
        Admin $admin,
        Screen $screen,
        array $reports,
        array $screenLogs,
        array $playbackLogs
    ): void {
        $now = Carbon::now();

        $dailyReport = $reports['daily_playback'] ?? null;
        $topAdsReport = $reports['top_ads'] ?? null;
        $latestScreenLog = $screenLogs[2] ?? ($screenLogs[0] ?? null);
        $latestPlayback = $playbackLogs[2] ?? ($playbackLogs[0] ?? null);
        $topAdsEntries = [];

        if ($topAdsReport) {
            $topAdsEntries = $topAdsReport->data['entries'] ?? [];
        }

        $entries = [
            [
                'log_name' => 'system',
                'description' => 'Generated daily playback summary',
                'event' => 'report.generated',
                'subject' => $dailyReport,
                'properties' => [
                    'filters' => $dailyReport?->filters ?? [],
                    'rows' => 24,
                ],
                'timestamp' => $now->copy()->subHours(5),
            ],
            [
                'log_name' => 'system',
                'description' => 'Identified top performing ads',
                'event' => 'report.generated',
                'subject' => $topAdsReport,
                'properties' => [
                    'filters' => $topAdsReport?->filters ?? [],
                    'entries' => $topAdsEntries,
                ],
                'timestamp' => $now->copy()->subHours(4),
            ],
            [
                'log_name' => 'monitoring',
                'description' => 'Screen SCR-INSIGHTS-001 heartbeat updated',
                'event' => 'screen.status',
                'subject' => $latestScreenLog,
                'properties' => [
                    'screen_code' => $screen->code,
                    'status' => $latestScreenLog?->status?->value ?? 'online',
                    'last_playback' => $latestPlayback?->played_at?->toDateTimeString(),
                ],
                'timestamp' => $now->copy()->subMinutes(20),
            ],
        ];

        foreach ($entries as $entry) {
            $subjectModel = $entry['subject'] ?? $screen;

            Activity::updateOrCreate(
                [
                    'log_name' => $entry['log_name'],
                    'description' => $entry['description'],
                ],
                [
                    'event' => $entry['event'],
                    'subject_type' => $subjectModel->getMorphClass(),
                    'subject_id' => $subjectModel->getKey(),
                    'causer_type' => Admin::class,
                    'causer_id' => $admin->id,
                    'properties' => $entry['properties'],
                    'batch_uuid' => (string) Str::uuid(),
                    'created_at' => $entry['timestamp'],
                    'updated_at' => $entry['timestamp'],
                ]
            );
        }
    }
}
