<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Screens\StoreScreenRequest;
use App\Http\Requests\Admin\Screens\UpdateScreenRequest;
use App\Models\Place;
use App\Models\Screen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function index(string $lang, Request $request): View
    {
        $query = Screen::query()
            ->with(['place', 'ads' => fn ($builder) => $builder->withPivot('play_order')->orderBy('ad_screen.play_order', 'asc')])
            ->withCount([
                'ads',
                'schedules as active_schedule_count' => fn ($builder) => $builder->where('is_active', true),
            ])
            ->latest('created_at');

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

        $screens = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Screen::count(),
            ScreenStatus::Online->value => Screen::where('status', ScreenStatus::Online->value)->count(),
            ScreenStatus::Offline->value => Screen::where('status', ScreenStatus::Offline->value)->count(),
            ScreenStatus::Maintenance->value => Screen::where('status', ScreenStatus::Maintenance->value)->count(),
        ];

        return view('admin.screens.index', [
            'pageName' => 'الشاشات',
            'lang' => $lang,
            'screens' => $screens,
            'places' => Place::orderBy('id')->get(),
            'filters' => [
                'status' => $request->input('status'),
                'place_id' => $request->input('place_id'),
                'search' => $request->input('search'),
            ],
            'statuses' => $this->availableStatuses(),
            'stats' => $stats,
        ]);
    }

    public function create(string $lang): View
    {
        $screen = new Screen([
            'status' => ScreenStatus::Offline,
        ]);

        return view('admin.screens.create', [
            'pageName' => 'إضافة شاشة جديدة',
            'lang' => $lang,
            'screen' => $screen,
            'places' => Place::orderBy('id')->get(),
            'statuses' => $this->availableStatuses(),
        ]);
    }

    public function store(string $lang, StoreScreenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $screen = Screen::create([
            'place_id' => $data['place_id'],
            'code' => $data['code'],
            'device_uid' => $data['device_uid'] ?? null,
            'status' => $data['status'],
            'last_heartbeat' => $data['last_heartbeat'] ? Carbon::parse($data['last_heartbeat']) : null,
        ]);

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'screen_id' => $screen->id,
            ])
            ->log('Created screen');

        return redirect()
            ->route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', __('Screen created successfully.'));
    }

    public function show(string $lang, Screen $screen): View
    {
        $screen->load([
            'place',
            'ads' => fn ($builder) => $builder->withPivot('play_order')->orderBy('ad_screen.play_order'),
            'schedules' => fn ($builder) => $builder->with('ad')->orderBy('start_time'),
        ]);

        $recentLogs = $screen->logs()->latest('reported_at')->paginate(20, ['*'], 'logs_page');
        $recentPlaybacks = $screen->playbacks()->with('ad')->latest('played_at')->paginate(20, ['*'], 'playbacks_page');

        $logsLastWeek = $screen->logs()
            ->where('reported_at', '>=', Carbon::now()->subDays(7))
            ->get();

        $onlineCount = $logsLastWeek->where('status', ScreenStatus::Online->value)->count();
        $offlineCount = $logsLastWeek->where('status', ScreenStatus::Offline->value)->count();
        $totalLogs = $logsLastWeek->count();
        $uptime = $totalLogs > 0
            ? round(($onlineCount / $totalLogs) * 100, 2)
            : null;

        $logSummary = [
            ScreenStatus::Online->value => $onlineCount,
            ScreenStatus::Offline->value => $offlineCount,
        ];

        return view('admin.screens.show', [
            'pageName' => 'تفاصيل الشاشة',
            'lang' => $lang,
            'screen' => $screen,
            'recentLogs' => $recentLogs,
            'recentPlaybacks' => $recentPlaybacks,
            'uptime' => $uptime,
            'logSummary' => $logSummary,
        ]);
    }

    public function edit(string $lang, Screen $screen): View
    {
        return view('admin.screens.edit', [
            'pageName' => 'تعديل الشاشة',
            'lang' => $lang,
            'screen' => $screen,
            'places' => Place::orderBy('id')->get(),
            'statuses' => $this->availableStatuses(),
        ]);
    }

    public function update(string $lang, UpdateScreenRequest $request, Screen $screen): RedirectResponse
    {
        $data = $request->validated();

        $screen->update([
            'place_id' => $data['place_id'],
            'code' => $data['code'],
            'device_uid' => $data['device_uid'] ?? null,
            'status' => $data['status'],
            'last_heartbeat' => $data['last_heartbeat'] ? Carbon::parse($data['last_heartbeat']) : null,
        ]);

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'screen_id' => $screen->id,
            ])
            ->log('Updated screen');

        return redirect()
            ->route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', __('Screen updated successfully.'));
    }

    public function destroy(string $lang, Screen $screen): RedirectResponse
    {
        $screenId = $screen->id;
        $screen->ads()->detach();
        $screen->delete();

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['screen_id' => $screenId])
            ->log('Deleted screen');

        return redirect()
            ->route('admin.screens.index', ['lang' => $lang])
            ->with('success', __('Screen deleted successfully.'));
    }

    private function availableStatuses(): array
    {
        return collect(ScreenStatus::cases())
            ->mapWithKeys(fn (ScreenStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }
}

