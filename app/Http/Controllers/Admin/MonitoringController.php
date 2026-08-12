<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Monitoring\AcknowledgeAlertRequest;
use App\Models\Place;
use App\Models\Screen;
use App\Services\Monitoring\ScreenAvailabilityService;
use App\Support\Lang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __construct(
        protected ScreenAvailabilityService $availability
    ) {}

    public function index(string $lang, Request $request): View
    {
        $query = Screen::query()
            // latestLog is a latestOfMany relation, so the "one row per screen"
            // intent is expressed in the model rather than in an eager-load
            // limit. The previous form was
            // `'logs' => fn ($b) => $b->latest('reported_at')->limit(1)`, which
            // was long documented as a bug that gave only one screen a latest
            // log — true on older Laravel, but 11+ rewrites such a limit into a
            // row_number() OVER (PARTITION BY ...) query, so it was in fact
            // correct here. The relation is still preferable: it hands Blade a
            // model instead of a one-element collection, and it does not depend
            // on the framework silently rewriting the query.
            ->with(['place', 'latestLog'])
            ->withCount([
                'logs as offline_logs_count' => fn ($builder) => $builder->where('status', ScreenStatus::Offline->value),
                'schedules as active_schedule_count' => fn ($builder) => $builder->where('is_active', true),
            ])
            ->orderBy('code');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('place_id')) {
            $query->where('place_id', (int) $request->input('place_id'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('device_uid', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('has_alerts')) {
            $query->where(function (Builder $builder) {
                $builder->where('status', ScreenStatus::Offline->value)
                    ->orWhereHas('logs', fn (Builder $logQuery) => $logQuery
                        ->where('status', ScreenStatus::Offline->value)
                        ->where('reported_at', '>=', now()->subDay()));
            });
        }

        $screens = $query->paginate(20)->withQueryString();

        $summary = [
            ScreenStatus::Online->value => Screen::where('status', ScreenStatus::Online->value)->count(),
            ScreenStatus::Offline->value => Screen::where('status', ScreenStatus::Offline->value)->count(),
            ScreenStatus::Maintenance->value => Screen::where('status', ScreenStatus::Maintenance->value)->count(),
        ];

        return view('admin.monitoring.index', [
            'pageName' => Lang::t('admin.pages.monitoring.index', 'مراقبة الشاشات'),
            'lang' => $lang,
            'screens' => $screens,
            'summary' => $summary,
            'filters' => [
                'status' => $request->input('status'),
                'place_id' => $request->input('place_id'),
                'search' => $request->input('search'),
                'has_alerts' => $request->boolean('has_alerts'),
            ],
            'statuses' => $this->availableStatuses(),
            'places' => Place::orderBy('id')->get(),
        ]);
    }

    public function showScreen(string $lang, Screen $screen): View
    {
        $screen->load([
            'place',
            'schedules' => fn ($builder) => $builder->with('ad')->orderBy('start_time'),
            'ads' => fn ($builder) => $builder->withPivot('play_order')->orderBy('ad_screen.play_order'),
        ]);

        $availability = $this->availability->forScreen($screen);

        $recentLogs = $screen->logs()->with('acknowledger')->latest('reported_at')->paginate(20, ['*'], 'logs_page');
        $recentPlaybacks = $screen->playbacks()->with('ad')->latest('played_at')->paginate(20, ['*'], 'playbacks_page');

        return view('admin.monitoring.show', [
            'pageName' => Lang::t('admin.pages.monitoring.show', 'حالة الشاشة'),
            'lang' => $lang,
            'screen' => $screen,
            'availability' => $availability,
            'openAlert' => $screen->openAlert()->with('acknowledger')->first(),
            'recentLogs' => $recentLogs,
            'recentPlaybacks' => $recentPlaybacks,
        ]);
    }

    /**
     * Record that an administrator has seen an operational alert.
     *
     * Acknowledgement is an administrative fact about an alert. It deliberately
     * does NOT touch `screens.status` or `screens.last_heartbeat`: before
     * Phase 11 it wrote both, so clicking this button made a dead screen report
     * as healthy and reset the very evidence that it was dead. Connectivity is
     * decided only by device heartbeats and the offline sweep.
     *
     * Putting a screen into maintenance is a separate, explicit action on the
     * Screen edit form.
     */
    public function acknowledgeAlert(string $lang, AcknowledgeAlertRequest $request, Screen $screen): RedirectResponse
    {
        $data = $request->validated();
        $admin = Auth::guard('admin')->user();

        $alert = $screen->openAlert()->first();

        if (! $alert) {
            return redirect()
                ->route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id])
                ->with('warning', Lang::t('admin.flash.monitoring.no_open_alert', 'There is no open alert to acknowledge.'));
        }

        // The offline event itself is preserved; acknowledgement is annotation.
        $alert->forceFill([
            'acknowledged_at' => now(),
            'acknowledged_by' => $admin?->id,
            'acknowledgement_note' => $data['note'] ?? null,
        ])->save();

        activity()
            ->performedOn($screen)
            ->causedBy($admin)
            ->withProperties([
                'screen_log_id' => $alert->id,
                'note' => $data['note'] ?? null,
            ])
            ->log('Acknowledged monitoring alert');

        return redirect()
            ->route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', Lang::t('admin.flash.monitoring.alert_acknowledged', 'Monitoring alert acknowledged.'));
    }

    private function availableStatuses(): array
    {
        return collect(ScreenStatus::cases())
            ->mapWithKeys(fn (ScreenStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }
}
