<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Screens\StoreScreenRequest;
use App\Http\Requests\Admin\Screens\UpdateScreenRequest;
use App\Models\Place;
use App\Models\Screen;
use App\Services\Monitoring\ScreenAvailabilityService;
use App\Services\Screen\DevicePairingService;
use App\Support\Lang;
use App\Support\ScreenHealth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function __construct(
        protected ScreenAvailabilityService $availability
    ) {}

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
            'pageName' => Lang::t('admin.pages.screens.index', 'الشاشات'),
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
            'pageName' => Lang::t('admin.pages.screens.create', 'إضافة شاشة جديدة'),
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
            // A brand-new screen has never been heard from, whatever the form
            // says. Maintenance is the only status an administrator can assert.
            'status' => $data['status'] === ScreenStatus::Maintenance->value
                ? ScreenStatus::Maintenance
                : ScreenStatus::Offline,
            'last_heartbeat' => null,
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
            ->with('success', Lang::t('admin.flash.screens.created', 'Screen created successfully.'));
    }

    public function show(string $lang, Screen $screen, DevicePairingService $pairing): View
    {
        $screen->load([
            'place',
            'ads' => fn ($builder) => $builder->withPivot('play_order')->orderBy('ad_screen.play_order'),
            'schedules' => fn ($builder) => $builder->with('ad')->orderBy('start_time'),
        ]);

        $recentLogs = $screen->logs()->with('acknowledger')->latest('reported_at')->paginate(20, ['*'], 'logs_page');
        $recentPlaybacks = $screen->playbacks()->with('ad')->latest('played_at')->paginate(20, ['*'], 'playbacks_page');

        $availability = $this->availability->forScreen($screen);

        // Counts of individual reports over the same window. Kept separate from
        // availability so the two are never confused again: these are events,
        // availability is elapsed time.
        $logSummary = $screen->logs()
            ->where('reported_at', '>=', $availability['period_start'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $credential = $pairing->activeCredential($screen);
        $livePairingCode = $pairing->livePairingCode($screen);

        return view('admin.screens.show', [
            'pageName' => Lang::t('admin.pages.screens.show', 'تفاصيل الشاشة'),
            'deviceCredential' => $credential,
            'livePairingCode' => $livePairingCode,
            'lang' => $lang,
            'screen' => $screen,
            'recentLogs' => $recentLogs,
            'recentPlaybacks' => $recentPlaybacks,
            'availability' => $availability,
            'logSummary' => $logSummary,
        ]);
    }

    public function edit(string $lang, Screen $screen): View
    {
        return view('admin.screens.edit', [
            'pageName' => Lang::t('admin.pages.screens.edit', 'تعديل الشاشة'),
            'lang' => $lang,
            'screen' => $screen,
            'places' => Place::orderBy('id')->get(),
            'statuses' => $this->availableStatuses(),
        ]);
    }

    public function update(string $lang, UpdateScreenRequest $request, Screen $screen, DevicePairingService $pairing): RedirectResponse
    {
        $data = $request->validated();

        $attributes = [
            'place_id' => $data['place_id'],
            'code' => $data['code'],
            'status' => $this->resolveStatus($screen, $data['status']),
        ];

        // A paired device owns its identity. Letting a routine screen edit
        // rewrite device_uid would silently reassign which hardware the screen
        // believes it is; re-pairing is the authoritative path. An unpaired
        // screen can still have its UID set by hand.
        if (! $pairing->activeCredential($screen)) {
            $attributes['device_uid'] = $data['device_uid'] ?? null;
        }

        // `last_heartbeat` is never in this array. It is written only by
        // HeartbeatService when a device actually reports in.
        $screen->update($attributes);

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'screen_id' => $screen->id,
            ])
            ->log('Updated screen');

        return redirect()
            ->route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', Lang::t('admin.flash.screens.updated', 'Screen updated successfully.'));
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
            ->with('success', Lang::t('admin.flash.screens.deleted', 'Screen deleted successfully.'));
    }

    /**
     * Issue a one-time pairing code so a device can claim this screen.
     *
     * The plaintext is flashed once, here; only its hash is persisted.
     */
    public function generatePairingCode(string $lang, Screen $screen, DevicePairingService $pairing): RedirectResponse
    {
        $result = $pairing->issuePairingCode($screen, Auth::guard('admin')->id());

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['screen_id' => $screen->id])
            ->log('Generated screen pairing code');

        return redirect()
            ->route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('pairing_code', $result['code'])
            ->with('pairing_code_expires_at', $result['expires_at']->toDateTimeString())
            ->with('success', Lang::t('admin.screens.pairing.code_generated', 'Pairing code generated.'));
    }

    /**
     * Revoke this screen's device credentials and retire any live pairing code.
     */
    public function resetDevice(string $lang, Screen $screen, DevicePairingService $pairing): RedirectResponse
    {
        $pairing->resetDevice($screen);

        activity()
            ->performedOn($screen)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['screen_id' => $screen->id])
            ->log('Reset screen device credentials');

        return redirect()
            ->route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])
            ->with('success', Lang::t('admin.screens.pairing.device_reset', 'Device credentials revoked.'));
    }

    /**
     * Decide the status an administrator's edit is allowed to produce.
     *
     * Maintenance is a genuine administrative decision, so it is honoured.
     * Anything else is a request to hand the screen back to automatic
     * connectivity tracking, and the answer comes from the evidence — the
     * freshness of `last_heartbeat` — not from the dropdown. That is what stops
     * an administrator declaring a dead screen online.
     */
    private function resolveStatus(Screen $screen, string $requested): ScreenStatus
    {
        if ($requested === ScreenStatus::Maintenance->value) {
            return ScreenStatus::Maintenance;
        }

        return ScreenHealth::isStale($screen->last_heartbeat)
            ? ScreenStatus::Offline
            : ScreenStatus::Online;
    }

    private function availableStatuses(): array
    {
        return collect(ScreenStatus::cases())
            ->mapWithKeys(fn (ScreenStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }
}

