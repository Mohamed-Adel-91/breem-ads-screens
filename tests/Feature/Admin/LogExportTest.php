<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\LogController;
use App\Models\Admin;
use App\Models\Screen;
use App\Models\ScreenLog;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LogExportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'first_name' => 'Stream',
            'last_name' => 'Tester',
            'email' => 'stream.tester@example.com',
            'password' => 'password',
            'mobile' => '1234567890',
        ]);

        Permission::findOrCreate('logs.export', 'admin');
        $this->admin->givePermissionTo('logs.export');
    }

    public function test_screen_log_download_streams_in_chunks(): void
    {
        $chunkSize = (new \ReflectionClass(LogController::class))->getConstant('STREAM_CHUNK_SIZE');
        $totalLogs = ($chunkSize * 2) + intdiv($chunkSize, 2);

        $screen = Screen::factory()->create();

        ScreenLog::factory()
            ->for($screen)
            ->count($totalLogs)
            ->sequence(function (Sequence $sequence) {
                return [
                    'reported_at' => now()->subMinutes($sequence->index),
                ];
            })
            ->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.logs.download', [
            'lang' => 'en',
            'type' => 'screen',
        ]));

        $csv = $response->streamedContent();

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv');

        $lines = preg_split("/(\r\n|\n|\r)/", trim($csv));

        $this->assertNotFalse($lines);
        $this->assertSame('Screen,Place,Status,Reported At', $lines[0]);
        $this->assertCount($totalLogs + 1, $lines);

        $selectQueries = collect(DB::getQueryLog())
            ->filter(function (array $query) {
                return str_contains($query['query'], 'from "screen_logs"')
                    || str_contains($query['query'], 'from `screen_logs`');
            });

        $this->assertGreaterThan(1, $selectQueries->count());

        DB::disableQueryLog();
    }
}

