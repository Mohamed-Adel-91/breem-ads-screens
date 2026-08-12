<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ads\StoreScheduleRequest;
use App\Http\Requests\Admin\Ads\UpdateScheduleRequest;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Place;
use App\Models\Screen;
use App\Support\Lang;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Per-ad schedule management, plus the cross-ad schedules overview.
 *
 * NO SILENT SIDE EFFECTS: saving a schedule writes that row and nothing else.
 * This controller used to run a `resolveScheduleConflicts()` pass that
 * deactivated every overlapping row on the same screen — including rows belonging
 * to *other advertisers* — so publishing one campaign silently took another off
 * the air. Overlapping windows are normal digital signage: when two ads are
 * eligible at once the playlist rotates them, which is exactly what a playlist is
 * for. Conflict resolution was therefore removed outright, not softened.
 *
 * Eligibility itself is never decided here; AdSchedulerService owns it.
 */
class ScheduleController extends Controller
{
    public function index(string $lang, Ad $ad, Request $request): View
    {
        $scheduleQuery = $ad->schedules()->with('screen.place')->orderBy('start_time');

        if ($request->filled('screen_id')) {
            $scheduleQuery->where('screen_id', (int) $request->input('screen_id'));
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $scheduleQuery->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('from_date')) {
            $scheduleQuery->where('start_time', '>=', Carbon::parse($request->input('from_date')));
        }

        if ($request->filled('to_date')) {
            $scheduleQuery->where('end_time', '<=', Carbon::parse($request->input('to_date')));
        }

        $schedules = $scheduleQuery->paginate(25)->withQueryString();

        $stats = [
            'total' => $ad->schedules()->count(),
            'active' => $ad->schedules()->where('is_active', true)->count(),
            'inactive' => $ad->schedules()->where('is_active', false)->count(),
        ];

        return view('admin.ads.schedules.index', [
            'pageName' => Lang::t('admin.pages.schedules.index', 'جداول عرض الإعلان'),
            'lang' => $lang,
            'ad' => $ad->loadMissing('screens.place'),
            'schedules' => $schedules,
            'availableScreens' => Screen::with('place')->orderBy('code')->get(),
            'filters' => [
                'screen_id' => $request->input('screen_id'),
                'is_active' => $request->input('is_active'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Every schedule row in the system, across ads.
     *
     * This is what the sidebar's "Schedules" entry opens. It used to point at
     * `/ads?tab=schedules`, a query parameter AdController never read, so the link
     * silently rendered the ads list instead.
     *
     * Rows are paginated and their relations eager-loaded; the per-row state badge
     * comes from AdSchedule::currentState(), which reads loaded attributes only.
     * Nothing is hydrated into memory in full and no query runs inside the view.
     */
    public function overview(string $lang, Request $request): View
    {
        $query = AdSchedule::query()
            ->with(['ad:id,title', 'screen:id,code,place_id', 'screen.place:id,name'])
            ->orderByDesc('start_time')
            ->orderByDesc('id');

        if ($request->filled('ad_id')) {
            $query->where('ad_id', (int) $request->input('ad_id'));
        }

        if ($request->filled('screen_id')) {
            $query->where('screen_id', (int) $request->input('screen_id'));
        }

        if ($request->filled('place_id')) {
            $placeId = (int) $request->input('place_id');
            $query->whereHas('screen', fn ($builder) => $builder->where('place_id', $placeId));
        }

        if ($request->filled('state') && in_array($request->input('state'), AdSchedule::states(), true)) {
            $query->inState((string) $request->input('state'));
        }

        if ($request->filled('from_date')) {
            $query->where('start_time', '>=', Carbon::parse($request->input('from_date')));
        }

        if ($request->filled('to_date')) {
            $query->where('end_time', '<=', Carbon::parse($request->input('to_date')));
        }

        $schedules = $query->paginate(25)->withQueryString();

        return view('admin.ads.schedules.overview', [
            'pageName' => Lang::t('admin.pages.schedules.overview', 'الجداول الزمنية'),
            'lang' => $lang,
            'schedules' => $schedules,
            'availableAds' => Ad::query()->orderBy('id')->get(['id', 'title']),
            'availableScreens' => Screen::with('place')->orderBy('code')->get(),
            'availablePlaces' => Place::orderBy('id')->get(['id', 'name']),
            'states' => AdSchedule::states(),
            'filters' => [
                'ad_id' => $request->input('ad_id'),
                'screen_id' => $request->input('screen_id'),
                'place_id' => $request->input('place_id'),
                'state' => $request->input('state'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ],
            'stats' => $this->overviewStats(),
        ]);
    }

    /**
     * Counts per state for the overview header. Four aggregate queries, not a
     * hydrated collection.
     *
     * @return array<string, int>
     */
    private function overviewStats(): array
    {
        $stats = ['total' => AdSchedule::count()];

        foreach (AdSchedule::states() as $state) {
            $stats[$state] = AdSchedule::query()->inState($state)->count();
        }

        return $stats;
    }

    public function store(string $lang, StoreScheduleRequest $request, Ad $ad): RedirectResponse
    {
        $data = $request->validated();

        $schedule = $ad->schedules()->create([
            'screen_id' => $data['screen_id'],
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => Carbon::parse($data['end_time']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->ensureScreenAttachment($ad, $schedule->screen_id);

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'schedule' => $schedule->id,
                'screen_id' => $schedule->screen_id,
            ])
            ->log('Created ad schedule');

        return redirect()
            ->route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', Lang::t('admin.flash.schedules.created', 'Schedule created successfully.'));
    }

    public function update(string $lang, UpdateScheduleRequest $request, Ad $ad, AdSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->ad_id !== $ad->id, 404);

        $data = $request->validated();
        $originalScreen = $schedule->screen_id;

        $schedule->update([
            'screen_id' => $data['screen_id'],
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => Carbon::parse($data['end_time']),
            'is_active' => $data['is_active'] ?? $schedule->is_active,
        ]);

        if ($originalScreen !== $schedule->screen_id) {
            $this->ensureScreenAttachment($ad, $schedule->screen_id);
        }

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'schedule' => $schedule->id,
            ])
            ->log('Updated ad schedule');

        return redirect()
            ->route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', Lang::t('admin.flash.schedules.updated', 'Schedule updated successfully.'));
    }

    public function destroy(string $lang, Ad $ad, AdSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->ad_id !== $ad->id, 404);

        $scheduleId = $schedule->id;
        $schedule->delete();

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['schedule' => $scheduleId])
            ->log('Deleted ad schedule');

        return redirect()
            ->route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', Lang::t('admin.flash.schedules.deleted', 'Schedule deleted successfully.'));
    }

    private function ensureScreenAttachment(Ad $ad, int $screenId): void
    {
        if (!$ad->screens()->where('screens.id', $screenId)->exists()) {
            $order = ($ad->screens()->max('ad_screen.play_order') ?? 0) + 1;
            $ad->screens()->attach($screenId, ['play_order' => $order]);

            $ad->flushScreensCache([$screenId]);
        }
    }

}
