<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\PlaybackLog;
use App\Models\Screen;
use App\Models\ScreenLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(string $lang, Request $request): View
    {
        $screenLogs = $this->screenLogsQuery($request)->paginate(20, ['*'], 'screen_page')->withQueryString();
        $playbackLogs = $this->playbackLogsQuery($request)->paginate(20, ['*'], 'playback_page')->withQueryString();

        return view('admin.logs.index', [
            'pageName' => 'سجلات النظام',
            'lang' => $lang,
            'screenLogs' => $screenLogs,
            'playbackLogs' => $playbackLogs,
            'filters' => [
                'screen_status' => $request->input('screen_status'),
                'screen_id' => $request->input('screen_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'ad_id' => $request->input('ad_id'),
                'played_from' => $request->input('played_from'),
                'played_to' => $request->input('played_to'),
            ],
            'statuses' => $this->availableStatuses(),
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'ads' => Ad::orderBy('id')->get(),
        ]);
    }

    public function download(string $lang, Request $request)
    {
        $type = $request->input('type', 'system');

        if ($type === 'system') {
            $path = storage_path('logs/laravel.log');
            if (!file_exists($path)) {
                return redirect()->route('admin.logs.index', ['lang' => $lang])->with('error', __('System log file not found.'));
            }

            activity()
                ->causedBy(Auth::guard('admin')->user())
                ->log('Downloaded system log file');

            return response()->download($path, 'laravel.log');
        }

        if ($type === 'screen') {
            $logs = $this->screenLogsQuery($request)->get();
            $filename = 'screen-logs-' . now()->format('Ymd_His') . '.csv';

            activity()
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['type' => 'screen'])
                ->log('Exported screen logs');

            return response()->streamDownload(function () use ($logs) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Screen', 'Place', 'Status', 'Reported At']);
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->screen?->code ?? '-',
                        $log->screen?->place?->getTranslation('name', app()->getLocale()) ?? '-',
                        $log->status->value ?? '-',
                        optional($log->reported_at)->toDateTimeString(),
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($type === 'playback') {
            $logs = $this->playbackLogsQuery($request)->get();
            $filename = 'playback-logs-' . now()->format('Ymd_His') . '.csv';

            activity()
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['type' => 'playback'])
                ->log('Exported playback logs');

            return response()->streamDownload(function () use ($logs) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Screen', 'Ad', 'Played At', 'Duration']);
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->screen?->code ?? '-',
                        $log->ad?->getTranslation('title', app()->getLocale()) ?? '-',
                        optional($log->played_at)->toDateTimeString(),
                        $log->duration,
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return redirect()->route('admin.logs.index', ['lang' => $lang])->with('error', __('Unsupported log export type.'));
    }

    private function screenLogsQuery(Request $request): Builder
    {
        $query = ScreenLog::query()->with('screen.place')->latest('reported_at');

        if ($request->filled('screen_status')) {
            $query->where('status', $request->input('screen_status'));
        }

        if ($request->filled('screen_id')) {
            $query->where('screen_id', $request->input('screen_id'));
        }

        if ($request->filled('from_date')) {
            $query->where('reported_at', '>=', Carbon::parse($request->input('from_date'))->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('reported_at', '<=', Carbon::parse($request->input('to_date'))->endOfDay());
        }

        return $query;
    }

    private function playbackLogsQuery(Request $request): Builder
    {
        $query = PlaybackLog::query()->with(['screen.place', 'ad'])->latest('played_at');

        if ($request->filled('screen_id')) {
            $query->where('screen_id', $request->input('screen_id'));
        }

        if ($request->filled('ad_id')) {
            $query->where('ad_id', $request->input('ad_id'));
        }

        if ($request->filled('played_from')) {
            $query->where('played_at', '>=', Carbon::parse($request->input('played_from'))->startOfDay());
        }

        if ($request->filled('played_to')) {
            $query->where('played_at', '<=', Carbon::parse($request->input('played_to'))->endOfDay());
        }

        return $query;
    }

    private function availableStatuses(): array
    {
        return collect(ScreenStatus::cases())
            ->mapWithKeys(fn (ScreenStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }
}
