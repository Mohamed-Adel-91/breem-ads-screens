<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ads\StoreAdRequest;
use App\Http\Requests\Admin\Ads\TransitionAdStatusRequest;
use App\Http\Requests\Admin\Ads\UpdateAdRequest;
use App\Models\Ad;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\AdSchedulerService;
use App\Support\CreativeMedia;
use App\Support\Lang;
use App\Support\VideoProbe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdController extends Controller
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly AdSchedulerService $scheduler
    )
    {
    }

    public function index(string $lang, Request $request): View
    {
        $query = Ad::query()
            ->with(['screens.place', 'creator'])
            // The index renders a schedule count per row. Counting in SQL avoids
            // one lazy-loaded schedules query per ad; the rendered value is
            // identical to the previous $ad->schedules->count().
            ->withCount('schedules')
            ->latest('created_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title->en', 'like', "%{$search}%")
                    ->orWhere('title->ar', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('screen_id')) {
            $screenId = (int) $request->input('screen_id');
            $query->whereHas('screens', function (Builder $builder) use ($screenId) {
                $builder->where('screens.id', $screenId);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('start_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('end_date', '<=', $request->input('to_date'));
        }

        $ads = $query->paginate(20)->withQueryString();

        $screens = Screen::with('place')->orderBy('code')->get();
        $owners = User::orderBy('name')->get();

        $stats = [
            'total' => Ad::count(),
            'active' => Ad::where('status', AdStatus::Active->value)->count(),
            'pending' => Ad::where('status', AdStatus::Pending->value)->count(),
            'expired' => Ad::where('status', AdStatus::Expired->value)->count(),
        ];

        return view('admin.ads.index', [
            'pageName' => Lang::t('admin.pages.ads.index', 'قائمة الإعلانات'),
            'lang' => $lang,
            'ads' => $ads,
            'statuses' => $this->availableStatuses(),
            'screens' => $screens,
            'owners' => $owners,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'screen_id' => $request->input('screen_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ],
            'stats' => $stats,
        ]);
    }

    public function create(string $lang): View
    {
        $ad = new Ad([
            'status' => AdStatus::Pending,
        ]);

        return view('admin.ads.create', [
            'pageName' => Lang::t('admin.pages.ads.create', 'إنشاء إعلان جديد'),
            'lang' => $lang,
            'ad' => $ad,
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'owners' => User::orderBy('name')->get(),
            'uploadLimits' => $this->uploadLimits(),
        ]);
    }

    public function store(string $lang, StoreAdRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $upload = $request->file('creative');

        // The category comes from the file's own contents, and the stored extension
        // is derived from it — never from the client filename.
        $fileType = CreativeMedia::categoryOf($upload);
        $filePath = $this->fileService->uploadSingle(
            $request,
            'creative',
            Ad::UPLOAD_FOLDER,
            null,
            CreativeMedia::extensionOf($upload)
        );

        $duration = $this->resolveDuration($fileType, $filePath, $data, null);

        if ($duration === null) {
            // The creative is already on disk at this point, so it must be removed
            // before the request unwinds; otherwise a failed probe leaves an orphan.
            return $this->failedProbe();
        }

        $ad = new Ad();

        try {
            DB::transaction(function () use ($ad, $data, $request, $filePath, $fileType, $duration): void {
                $ad->title = $this->prepareTranslations($data['title'] ?? []);
                $ad->description = $this->prepareTranslations($data['description'] ?? []);
                $ad->file_path = $filePath;
                $ad->file_type = $fileType;
                $ad->duration_seconds = $duration;
                // A new ad is always pending: publishing is an approval action, not
                // a field on this form.
                $ad->status = AdStatus::Pending;
                $ad->created_by = $data['created_by'];
                $ad->created_by_admin_id = Auth::guard('admin')->id();
                $ad->start_date = $data['start_date'] ?? null;
                $ad->end_date = $data['end_date'] ?? null;
                $ad->save();

                $this->syncScreens($ad, $data['screens'] ?? [], $request->input('play_order', []));
            });
        } catch (Throwable $e) {
            $this->fileService->discardUploadedFiles();

            throw $e;
        }

        // Nothing was replaced on a create, but the pending list is still cleared so
        // the service does not carry state into anything else in this request.
        $this->fileService->commitReplacedFiles();

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'status' => $ad->status->value,
                'screens' => $ad->screens->pluck('id')->toArray(),
            ])
            ->log('Created ad');

        return redirect()
            ->route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', Lang::t('admin.flash.ads.created', 'Ad created successfully.'));
    }

    public function show(string $lang, Ad $ad): View
    {
        $ad->load([
            'screens.place',
            'schedules' => fn ($query) => $query->with('screen.place')->orderBy('start_time'),
            'creator',
            'approver',
            'creatorAdmin',
            'approverAdmin',
            'playbacks' => fn ($query) => $query->with('screen')->latest('played_at')->limit(20),
        ]);

        $playbackStats = $ad->playbacks
            ->groupBy(fn ($item) => optional($item->played_at)->format('Y-m-d'))
            ->map(fn ($items) => [
                'plays' => $items->count(),
                'duration' => $items->sum('duration'),
            ])
            ->sortKeysDesc();

        return view('admin.ads.show', [
            'pageName' => Lang::t('admin.pages.ads.show', 'تفاصيل الإعلان'),
            'lang' => $lang,
            'ad' => $ad,
            'playbackStats' => $playbackStats,
            'upcomingSchedules' => $ad->schedules->filter(fn ($schedule) => $schedule->start_time?->isFuture()),
            'pastSchedules' => $ad->schedules->filter(fn ($schedule) => $schedule->end_time?->isPast()),
            // Resolved here, not in Blade: the view renders the edges the ad's
            // current status actually permits.
            'availableActions' => array_keys($ad->status->allowedTransitions()),
            'validFrom' => $ad->validFrom(),
            'validBefore' => $ad->validBefore(),
        ]);
    }

    public function edit(string $lang, Ad $ad): View
    {
        $ad->load('screens');

        return view('admin.ads.edit', [
            'pageName' => Lang::t('admin.pages.ads.edit', 'تعديل الإعلان'),
            'lang' => $lang,
            'ad' => $ad,
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'owners' => User::orderBy('name')->get(),
            'uploadLimits' => $this->uploadLimits(),
        ]);
    }

    public function update(string $lang, UpdateAdRequest $request, Ad $ad): RedirectResponse
    {
        $data = $request->validated();
        $upload = $request->file('creative');

        // Order matters throughout: the new file is written first, every way the
        // write can fail is handled before the old one is touched, and the old file
        // is removed only after the database has committed.
        if ($upload) {
            $fileType = CreativeMedia::categoryOf($upload);
            $filePath = $this->fileService->uploadSingle(
                $request,
                'creative',
                Ad::UPLOAD_FOLDER,
                $ad->file_path,
                CreativeMedia::extensionOf($upload)
            );
        } else {
            $fileType = $ad->file_type;
            $filePath = $ad->file_path;
        }

        $fileChanged = (bool) $upload && $filePath !== $ad->file_path;

        $duration = $fileChanged
            ? $this->resolveDuration($fileType, $filePath, $data, null)
            : $this->resolveDuration($fileType, $filePath, $data, (int) $ad->duration_seconds);

        if ($duration === null) {
            // Nothing has been written to the ad row, so the database still points at
            // the old creative and that file is still on disk. Only the new candidate
            // is thrown away.
            return $this->failedProbe();
        }

        $requiresReapproval = $this->requiresReapproval($ad, $filePath, $fileType, $duration, $data);

        try {
            DB::transaction(function () use ($ad, $data, $request, $filePath, $fileType, $duration, $requiresReapproval): void {
                $ad->title = $this->prepareTranslations($data['title'] ?? []);
                $ad->description = $this->prepareTranslations($data['description'] ?? []);
                $ad->file_path = $filePath;
                $ad->file_type = $fileType;
                $ad->duration_seconds = $duration;
                $ad->created_by = $data['created_by'];
                $ad->start_date = $data['start_date'] ?? null;
                $ad->end_date = $data['end_date'] ?? null;

                if ($requiresReapproval) {
                    // What a screen would play has changed, so the previous review no
                    // longer covers it. The approval trail is cleared with the status
                    // it belonged to.
                    $ad->status = AdStatus::Pending;
                    $ad->approved_by_admin_id = null;
                    $ad->approved_at = null;
                }

                $ad->save();

                $this->syncScreens($ad, $data['screens'] ?? [], $request->input('play_order', []));
            });
        } catch (Throwable $e) {
            $this->fileService->discardUploadedFiles();

            throw $e;
        }

        // Committed: the replaced creative can finally go.
        $this->fileService->commitReplacedFiles();

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'status' => $ad->status->value,
                'creative_replaced' => $fileChanged,
                'reapproval_required' => $requiresReapproval,
            ])
            ->log('Updated ad');

        $message = $requiresReapproval
            ? Lang::t('admin.flash.ads.updated_pending_review', 'Ad updated. Playback-relevant changes need approval again.')
            : Lang::t('admin.flash.ads.updated', 'Ad updated successfully.');

        return redirect()
            ->route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', $message);
    }

    /**
     * Move an advertisement along one declared lifecycle edge.
     *
     * Separate from update() on purpose. Approval is an authority, not a form field:
     * this action is gated by `ads.approve`, takes an action name rather than a
     * target status, and refuses any edge AdStatus does not declare from the ad's
     * current status.
     */
    public function transition(string $lang, TransitionAdStatusRequest $request, Ad $ad): RedirectResponse
    {
        $action = $request->action();
        $from = $ad->status;
        $to = $from->resultOf($action);

        if (! $to) {
            return back()->withErrors([
                'action' => Lang::t(
                    'admin.ads.transitions.not_allowed',
                    'That action is not available for an ad in its current state.'
                ),
            ]);
        }

        $approver = Auth::guard('admin')->user();

        $ad->status = $to;

        if ($action === AdStatus::ACTION_APPROVE) {
            $ad->approved_by_admin_id = $approver?->id;
            $ad->approved_at = now();
        }

        // Saving the ad flushes every assigned screen's playlist through AdObserver,
        // so an approval or takedown reaches devices on their next poll rather than
        // after the cache TTL.
        $ad->save();

        activity()
            ->performedOn($ad)
            ->causedBy($approver)
            ->withProperties(array_filter([
                'action' => $action,
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $request->reason(),
            ], fn ($value) => $value !== null))
            ->log('Changed ad status');

        return redirect()
            ->route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', Lang::t(
                'admin.flash.ads.status_changed',
                'Ad status updated.'
            ));
    }

    /**
     * Delete an advertisement and, only afterwards, the creative it owned.
     *
     * The order is deliberate:
     *   1. capture the affected screens while the pivot rows still exist;
     *   2. remove the database rows;
     *   3. invalidate those screens' playlists, so no device is still being handed
     *      a creative that is about to disappear;
     *   4. delete the file last, and only if this ad was the only record pointing
     *      at it.
     *
     * If step 2 fails, nothing is deleted from disk at all.
     */
    public function destroy(string $lang, Ad $ad): RedirectResponse
    {
        $filePath = $ad->file_path;

        $screenIds = $ad->screens()->pluck('screens.id')->all();

        // A creative is normally owned by one ad, but a path can be reused — by a
        // duplicated row, or by a seeded asset shared between ads. Check before
        // deleting, because an unlinked file cannot be recovered.
        $isSharedCreative = $filePath
            && Ad::query()
                ->where('file_path', $filePath)
                ->whereKeyNot($ad->getKey())
                ->exists();

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['id' => $ad->id])
            ->log('Deleted ad');

        DB::transaction(function () use ($ad): void {
            $ad->screens()->detach();
            $ad->delete();
        });

        $screenIds = collect($screenIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($screenIds)) {
            $this->scheduler->forgetMany($screenIds);
        }

        if ($filePath && ! $isSharedCreative) {
            $this->fileService->deleteFile(basename($filePath), Ad::UPLOAD_FOLDER);
        }

        return redirect()
            ->route('admin.ads.index', ['lang' => $lang])
            ->with('success', Lang::t('admin.flash.ads.deleted', 'Ad deleted successfully.'));
    }

    /**
     * Status list for the index filter. It is no longer a form field — the create
     * and edit forms carry no status select at all.
     *
     * @return array<string, string>
     */
    private function availableStatuses(): array
    {
        return collect(AdStatus::cases())
            ->mapWithKeys(fn (AdStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
    }

    /**
     * Accepted formats and size ceilings, for the form's helper text.
     *
     * Read from CreativeMedia so the text can never advertise a limit the validator
     * does not enforce.
     *
     * @return array<string, mixed>
     */
    private function uploadLimits(): array
    {
        return [
            'accept' => CreativeMedia::allowedMimeTypeList(),
            'image_max_kb' => CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_IMAGE),
            'gif_max_kb' => CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_GIF),
            'video_max_kb' => CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_VIDEO),
        ];
    }

    private function prepareTranslations(?array $values): array
    {
        return collect($values ?? [])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->toArray();
    }

    private function syncScreens(Ad $ad, array $screens, array $playOrders): void
    {
        $previousScreenIds = $ad->screens()->pluck('screens.id')->all();

        $syncData = [];
        foreach ($screens as $screenId) {
            $syncData[$screenId] = [
                'play_order' => (int) ($playOrders[$screenId] ?? 0),
            ];
        }

        if (!empty($syncData)) {
            $ad->screens()->sync($syncData);
        } else {
            $ad->screens()->detach();
        }

        $affectedScreenIds = array_unique(array_merge($previousScreenIds, array_keys($syncData)));

        $ad->flushScreensCache($affectedScreenIds);
    }

    /**
     * The single duration calculation for both create and update.
     *
     * PRECEDENCE, in order:
     *   1. an explicit non-zero `duration_seconds` from the operator always wins;
     *   2. otherwise, for a video, ffprobe reads it from the file itself;
     *   3. otherwise the current value is kept (update) or zero (create).
     *
     * Images and GIFs are never probed — how long a still is shown is a playlist
     * decision, not a property of the file.
     *
     * Returns **null** to mean "probing was required and failed", which the callers
     * turn into a validation error after cleaning up the uploaded candidate. It is
     * never a valid duration, so the ad row can never be written with an
     * unknown-duration video.
     *
     * @param  array<string, mixed>  $data  validated request data
     */
    private function resolveDuration(string $fileType, ?string $filePath, array $data, ?int $currentDuration): ?int
    {
        $requested = array_key_exists('duration_seconds', $data) && $data['duration_seconds'] !== null
            ? (int) $data['duration_seconds']
            : null;

        if ($requested !== null && $requested > 0) {
            return $requested;
        }

        $fallback = $requested ?? $currentDuration ?? 0;

        if (! CreativeMedia::requiresProbedDuration($fileType)) {
            return $fallback;
        }

        // A video already carrying a duration (an edit that did not replace the file)
        // needs no probe.
        if ($requested === null && ($currentDuration ?? 0) > 0) {
            return $currentDuration;
        }

        if (! config('ads.try_ffprobe', true)) {
            return $fallback;
        }

        // Deliberately outside any database transaction: ffprobe shells out to an
        // external binary and must not hold a transaction open.
        return VideoProbe::durationSeconds($filePath);
    }

    /**
     * Abandon the uploaded candidate and send the operator back with an error.
     */
    private function failedProbe(): RedirectResponse
    {
        $this->fileService->discardUploadedFiles();

        return back()
            ->withInput()
            ->withErrors([
                'duration_seconds' => 'duration_seconds required (ffprobe unavailable or failed)',
            ]);
    }

    /**
     * Does this edit invalidate the creative review the ad already passed?
     *
     * Only for an ad that has actually been reviewed (`approved` or `active`), and
     * only when an attribute the device consumes has genuinely changed value —
     * re-saving the same values is not a change. Title and description are excluded
     * because they never reach a screen.
     *
     * Assignment, schedules and play order are not ad attributes and are handled
     * elsewhere; changing them never revokes creative approval.
     *
     * @param  array<string, mixed>  $data
     */
    private function requiresReapproval(Ad $ad, ?string $filePath, string $fileType, int $duration, array $data): bool
    {
        if (! $ad->status->isReviewed()) {
            return false;
        }

        $incoming = [
            'file_path' => $filePath,
            'file_type' => $fileType,
            'duration_seconds' => $duration,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];

        foreach (Ad::PLAYBACK_RELEVANT_ATTRIBUTES as $attribute) {
            if ($this->attributeChanged($ad, $attribute, $incoming[$attribute])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare a stored attribute with its incoming value, normalising dates so a
     * re-submitted identical date does not read as a change.
     */
    private function attributeChanged(Ad $ad, string $attribute, mixed $incoming): bool
    {
        $current = $ad->getOriginal($attribute);

        if (in_array($attribute, ['start_date', 'end_date'], true)) {
            $currentValue = $current ? Carbon::parse($current)->toDateTimeString() : null;
            $incomingValue = $incoming ? Carbon::parse($incoming)->toDateTimeString() : null;

            return $currentValue !== $incomingValue;
        }

        if ($attribute === 'duration_seconds') {
            return (int) $current !== (int) $incoming;
        }

        return (string) $current !== (string) $incoming;
    }
}

