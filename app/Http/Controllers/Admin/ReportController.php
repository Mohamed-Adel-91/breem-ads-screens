<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Models\Ad;
use App\Models\Report;
use App\Models\Screen;
use App\Services\Reports\ReportGenerationService;
use App\Support\Lang;
use App\Support\ReportType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Reports: HTTP, authorization and presentation.
 *
 * Generation itself lives in ReportGenerationService — it has real rules (period
 * semantics, SQL aggregation, and availability that must match Monitoring exactly),
 * and it used to be ~90 lines of query and Collection work inline here.
 *
 * Report types come from App\Support\ReportType, the single registry.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportGenerationService $reports
    ) {
    }

    public function index(string $lang, Request $request): View
    {
        // Only the columns the table actually renders. `reports.data` is the whole
        // snapshot — every aggregated row of every listed report — and the index shows
        // none of it, so selecting it read the entire page's payloads out of the
        // database and JSON-decoded them through the `array` cast for nothing.
        // `generated_by` is here because the `generator` relation is the foreign key.
        // Anything needing `data` (show, download) loads the model in full.
        $query = Report::query()
            ->select(['id', 'name', 'type', 'generated_by', 'created_at'])
            ->with('generator')
            ->latest('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.reports.index', [
            'pageName' => Lang::t('admin.pages.reports.index', 'التقارير'),
            'lang' => $lang,
            'reports' => $reports,
            'filters' => [
                'type' => $request->input('type'),
                'search' => $request->input('search'),
            ],
            // Only generatable types are offered; legacy values remain readable on
            // existing rows but are never offered for a new report.
            'types' => ReportType::supported(),
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'ads' => Ad::orderBy('id')->get(['id', 'title']),
        ]);
    }

    public function generate(string $lang, GenerateReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $filters = collect($data)
            ->only(['from_date', 'to_date', 'screen_id', 'ad_id'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->toArray();

        // Build first, persist second, inside a transaction. A failed generation must
        // not leave behind a Report row that looks valid but holds nothing — the
        // caller sees the error and no snapshot exists.
        try {
            $report = DB::transaction(function () use ($data, $filters) {
                return Report::create([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'filters' => $filters,
                    'data' => $this->reports->build($data['type'], $filters),
                    'generated_by' => Auth::guard('admin')->id(),
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['type' => Lang::t(
                    'admin.reports.generation_failed',
                    'The report could not be generated. The error has been logged.'
                )]);
        }

        activity()
            ->performedOn($report)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['report_id' => $report->id])
            ->log('Generated report');

        return redirect()
            ->route('admin.reports.show', ['lang' => $lang, 'report' => $report->id])
            ->with('success', Lang::t('admin.flash.reports.generated', 'Report generated successfully.'));
    }

    /**
     * A stored report.
     *
     * Everything rendered comes from the immutable snapshot in `reports.data` — the
     * page runs no log queries, so it shows the same figures for ever and keeps
     * working after the source logs have been pruned.
     */
    public function show(string $lang, Report $report): View
    {
        $data = is_array($report->data) ? $report->data : [];

        return view('admin.reports.show', [
            'pageName' => $report->name,
            'lang' => $lang,
            'report' => $report,
            'rows' => $data['rows'] ?? [],
            // Resolved here so Blade neither computes nor guesses.
            'canonicalType' => ReportType::canonical($report->type),
            'isPresentable' => ReportType::isPresentable($report->type),
            'isLegacyType' => ReportType::isLegacy($report->type),
            'summary' => $data['summary'] ?? [],
            'period' => $data['period'] ?? [],
        ]);
    }

    /**
     * Stream the snapshot as CSV.
     *
     * The body is written straight to the output stream a row at a time, so it is
     * never assembled in memory. The row set is the stored aggregate — one row per
     * advertisement or per screen — not raw log rows.
     */
    public function download(string $lang, Report $report)
    {
        $filename = Str::slug($report->name ?: 'report') . '-' . now()->format('Ymd_His') . '.csv';
        $type = $report->type;
        $rows = is_array($report->data) ? ($report->data['rows'] ?? []) : [];

        activity()
            ->performedOn($report)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['report_id' => $report->id])
            ->log('Downloaded report');

        return response()->streamDownload(function () use ($type, $rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ReportGenerationService::headers($type));

            foreach ($rows as $row) {
                fputcsv($handle, ReportGenerationService::formatRow($type, (array) $row));

                // Push each row out rather than buffering the whole file.
                flush();
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
