<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ads\StoreScheduleRequest;
use App\Http\Requests\Admin\Ads\UpdateScheduleRequest;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Screen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
            'pageName' => 'جداول عرض الإعلان',
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
        $this->resolveScheduleConflicts($schedule);

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
            ->with('success', __('Schedule created successfully.'));
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

        $this->resolveScheduleConflicts($schedule);

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'schedule' => $schedule->id,
            ])
            ->log('Updated ad schedule');

        return redirect()
            ->route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', __('Schedule updated successfully.'));
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
            ->with('success', __('Schedule deleted successfully.'));
    }

    private function ensureScreenAttachment(Ad $ad, int $screenId): void
    {
        if (!$ad->screens()->where('screens.id', $screenId)->exists()) {
            $order = ($ad->screens()->max('ad_screen.play_order') ?? 0) + 1;
            $ad->screens()->attach($screenId, ['play_order' => $order]);
        }
    }

    private function resolveScheduleConflicts(AdSchedule $schedule): void
    {
        $schedule->loadMissing('ad');

        $conflicts = AdSchedule::query()
            ->where('screen_id', $schedule->screen_id)
            ->where('id', '!=', $schedule->id)
            ->where(function (Builder $builder) use ($schedule) {
                $builder->whereBetween('start_time', [$schedule->start_time, $schedule->end_time])
                    ->orWhereBetween('end_time', [$schedule->start_time, $schedule->end_time])
                    ->orWhere(function (Builder $nested) use ($schedule) {
                        $nested->where('start_time', '<=', $schedule->start_time)
                            ->where('end_time', '>=', $schedule->end_time);
                    });
            })
            ->get();

        $deactivated = [];

        foreach ($conflicts as $conflict) {
            if ($conflict->is_active) {
                $conflict->update(['is_active' => false]);
                $deactivated[] = $conflict->id;
            }
        }

        if ($deactivated) {
            activity()
                ->performedOn($schedule->ad)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'schedule' => $schedule->id,
                    'deactivated_conflicts' => $deactivated,
                ])
                ->log('Resolved schedule conflicts');
        }
    }
}
