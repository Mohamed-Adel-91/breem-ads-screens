<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Monitoring\AcknowledgeAlertRequest;
use App\Models\Place;
use App\Models\Screen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(string $lang, Request $request): View
    {
        $query = Screen::query()
            ->with([
                'place',
                'logs' => fn ($builder) => $builder->latest('reported_at')->limit(1),
            ])
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
            'pageName' => 'مراقبة الشاشات',
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

        $logsLastWeek = $screen->logs()->where('reported_at', '>=', Carbon::now()->subDays(7))->get();
        $onlineCount = $logsLastWeek->where('status', ScreenStatus::Online->value)->count();
        $uptime = $logsLastWeek->count() > 0
            ? round(($onlineCount / $logsLastWeek->count()) * 100, 2)
            : null;

        $recentLogs = $screen->logs()->latest('reported_at')->paginate(20, ['*'], 'logs_page');
        $recentPlaybacks = $screen->playbacks()->with('ad')->latest('played_at')->paginate(20, ['*'], 'playbacks_page');

        return view('admin.monitoring.show', [
            'pageName' => 'حالة الشاشة',
            'lang' => $lang,
            'screen' => $screen,
            'uptime' => $uptime,
            'recentLogs' => $recentLogs,
            'recentPlaybacks' => $recentPlaybacks,
        ]);
    }

    public function acknowledgeAlert(string $lang, AcknowledgeAlertRequest $request, Screen $screen): RedirectResponse
    {
        $data = $request->validated();

        $screen->status = $data['status'];
        $screen->last_heartbeat = now();
        $screen->save();

        $screen->logs()->create([
            'status' => $data['status'],
            'reported_at' => now(),
            'current_ad_code' => null,
        ]);

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
            ])
            ->log('Acknowledged monitoring alert');

        return redirect()
            ->route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', __('Monitoring alert acknowledged.'));
    }

    private function availableStatuses(): array
    {
        return collect(ScreenStatus::cases())
            ->mapWithKeys(fn (ScreenStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }
}
