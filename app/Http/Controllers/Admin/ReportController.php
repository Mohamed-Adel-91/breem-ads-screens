<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Models\Ad;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\Screen;
use App\Models\ScreenLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(string $lang, Request $request): View
    {
        $query = Report::query()->with('generator')->latest('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.reports.index', [
            'pageName' => 'التقارير',
            'lang' => $lang,
            'reports' => $reports,
            'filters' => [
                'type' => $request->input('type'),
                'search' => $request->input('search'),
            ],
            'types' => GenerateReportRequest::TYPES,
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'ads' => Ad::orderBy('id')->get(),
        ]);
    }

    public function generate(string $lang, GenerateReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $report = Report::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'filters' => collect($data)->only(['from_date', 'to_date', 'screen_id', 'ad_id'])->filter(fn ($value) => $value !== null && $value !== '')->toArray(),
            'data' => $this->buildReportData($data),
            'generated_by' => Auth::guard('admin')->id(),
        ]);

        activity()
            ->performedOn($report)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['report_id' => $report->id])
            ->log('Generated report');

        return redirect()
            ->route('admin.reports.show', ['lang' => $lang, 'report' => $report->id])
            ->with('success', __('Report generated successfully.'));
    }

    public function show(string $lang, Report $report): View
    {
        return view('admin.reports.show', [
            'pageName' => $report->name,
            'lang' => $lang,
            'report' => $report,
            'rows' => $report->data['rows'] ?? [],
        ]);
    }

    public function download(string $lang, Report $report)
    {
        $filename = Str::slug($report->name ?: 'report') . '-' . now()->format('Ymd_His') . '.csv';
        $headers = $this->reportHeaders($report->type);
        $rows = $report->data['rows'] ?? [];

        activity()
            ->performedOn($report)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['report_id' => $report->id])
            ->log('Downloaded report');

        return response()->streamDownload(function () use ($headers, $rows, $report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $this->formatRow($report->type, $row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildReportData(array $filters): array
    {
        return match ($filters['type']) {
            'screen-uptime' => $this->buildScreenUptimeReport($filters),
            default => $this->buildPlaybackReport($filters),
        };
    }

    private function buildPlaybackReport(array $filters): array
    {
        $query = PlaybackLog::query()->with(['ad', 'screen']);

        if (!empty($filters['from_date'])) {
            $query->where('played_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->where('played_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        if (!empty($filters['screen_id'])) {
            $query->where('screen_id', $filters['screen_id']);
        }

        if (!empty($filters['ad_id'])) {
            $query->where('ad_id', $filters['ad_id']);
        }

        $logs = $query->get();

        $rows = $logs->groupBy('ad_id')->map(function ($collection) {
            $ad = $collection->first()?->ad;
            $screens = $collection->map(fn ($log) => $log->screen?->code)->filter()->unique()->values()->all();

            return [
                'ad_id' => $ad?->id,
                'ad_title' => $ad?->getTranslation('title', app()->getLocale()) ?? '—',
                'plays' => $collection->count(),
                'total_duration' => $collection->sum('duration'),
                'screens' => $screens,
            ];
        })->values()->all();

        return [
            'rows' => $rows,
            'generated_at' => now()->toDateTimeString(),
            'total_logs' => $logs->count(),
        ];
    }

    private function buildScreenUptimeReport(array $filters): array
    {
        $query = ScreenLog::query()->with(['screen.place']);

        if (!empty($filters['from_date'])) {
            $query->where('reported_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->where('reported_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        if (!empty($filters['screen_id'])) {
            $query->where('screen_id', $filters['screen_id']);
        }

        $logs = $query->get();

        $rows = $logs->groupBy('screen_id')->map(function ($collection) {
            $screen = $collection->first()?->screen;
            $placeName = $screen?->place?->getTranslation('name', app()->getLocale()) ?? '-';
            $sorted = $collection->sortBy('reported_at');

            return [
                'screen_id' => $screen?->id,
                'screen_code' => $screen?->code,
                'place' => $placeName,
                'online_events' => $collection->where('status', ScreenStatus::Online->value)->count(),
                'offline_events' => $collection->where('status', ScreenStatus::Offline->value)->count(),
                'last_status' => $collection->sortByDesc('reported_at')->first()?->status?->value ?? null,
                'period_start' => optional($sorted->first()?->reported_at)->toDateTimeString(),
                'period_end' => optional($sorted->last()?->reported_at)->toDateTimeString(),
            ];
        })->values()->all();

        return [
            'rows' => $rows,
            'generated_at' => now()->toDateTimeString(),
            'total_logs' => $logs->count(),
        ];
    }

    private function reportHeaders(string $type): array
    {
        return match ($type) {
            'screen-uptime' => ['Screen ID', 'Code', 'Place', 'Online Events', 'Offline Events', 'Last Status', 'Period Start', 'Period End'],
            default => ['Ad ID', 'Ad Title', 'Plays', 'Total Duration', 'Screens'],
        };
    }

    private function formatRow(string $type, array $row): array
    {
        return match ($type) {
            'screen-uptime' => [
                $row['screen_id'] ?? '',
                $row['screen_code'] ?? '',
                $row['place'] ?? '',
                $row['online_events'] ?? 0,
                $row['offline_events'] ?? 0,
                $row['last_status'] ?? '',
                $row['period_start'] ?? '',
                $row['period_end'] ?? '',
            ],
            default => [
                $row['ad_id'] ?? '',
                $row['ad_title'] ?? '',
                $row['plays'] ?? 0,
                $row['total_duration'] ?? 0,
                implode(', ', $row['screens'] ?? []),
            ],
        };
    }
}
