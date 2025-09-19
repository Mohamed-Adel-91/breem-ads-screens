<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ads\StoreAdRequest;
use App\Http\Requests\Admin\Ads\UpdateAdRequest;
use App\Models\Ad;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdController extends Controller
{
    public function __construct(private readonly FileServiceInterface $fileService)
    {
    }

    public function index(string $lang, Request $request): View
    {
        $query = Ad::query()
            ->with(['screens.place', 'creator'])
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
            'pageName' => 'قائمة الإعلانات',
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
            'pageName' => 'إنشاء إعلان جديد',
            'lang' => $lang,
            'ad' => $ad,
            'statuses' => $this->availableStatuses(),
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function store(string $lang, StoreAdRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $filePath = $this->fileService->uploadSingle($request, 'creative', Ad::UPLOAD_FOLDER);

        $ad = new Ad();
        $ad->title = $this->prepareTranslations($data['title'] ?? []);
        $ad->description = $this->prepareTranslations($data['description'] ?? []);
        $ad->file_path = $filePath;
        $ad->file_type = $this->determineFileType($request->file('creative'), $filePath);
        $ad->duration_seconds = (int) ($data['duration_seconds'] ?? 0);
        $ad->status = AdStatus::from($data['status'] ?? AdStatus::Pending->value);
        $ad->created_by = $data['created_by'];
        $ad->approved_by = $data['approved_by'] ?? null;
        $ad->start_date = $data['start_date'] ?? null;
        $ad->end_date = $data['end_date'] ?? null;
        $ad->save();

        $this->syncScreens($ad, $data['screens'] ?? [], $request->input('play_order', []));

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
            ->with('success', __('Ad created successfully.'));
    }

    public function show(string $lang, Ad $ad): View
    {
        $ad->load([
            'screens.place',
            'schedules' => fn ($query) => $query->with('screen.place')->orderBy('start_time'),
            'creator',
            'approver',
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
            'pageName' => 'تفاصيل الإعلان',
            'lang' => $lang,
            'ad' => $ad,
            'playbackStats' => $playbackStats,
            'upcomingSchedules' => $ad->schedules->filter(fn ($schedule) => $schedule->start_time?->isFuture()),
            'pastSchedules' => $ad->schedules->filter(fn ($schedule) => $schedule->end_time?->isPast()),
        ]);
    }

    public function edit(string $lang, Ad $ad): View
    {
        $ad->load('screens');

        return view('admin.ads.edit', [
            'pageName' => 'تعديل الإعلان',
            'lang' => $lang,
            'ad' => $ad,
            'statuses' => $this->availableStatuses(),
            'screens' => Screen::with('place')->orderBy('code')->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function update(string $lang, UpdateAdRequest $request, Ad $ad): RedirectResponse
    {
        $data = $request->validated();

        $filePath = $this->fileService->uploadSingle($request, 'creative', Ad::UPLOAD_FOLDER, $ad->file_path);
        if ($filePath && $filePath !== $ad->file_path) {
            $ad->file_path = $filePath;
            $ad->file_type = $this->determineFileType($request->file('creative'), $filePath);
        }

        $ad->title = $this->prepareTranslations($data['title'] ?? []);
        $ad->description = $this->prepareTranslations($data['description'] ?? []);
        $ad->duration_seconds = (int) ($data['duration_seconds'] ?? $ad->duration_seconds);
        $ad->status = AdStatus::from($data['status'] ?? $ad->status->value);
        $ad->created_by = $data['created_by'];
        $ad->approved_by = $data['approved_by'] ?? null;
        $ad->start_date = $data['start_date'] ?? null;
        $ad->end_date = $data['end_date'] ?? null;
        $ad->save();

        $this->syncScreens($ad, $data['screens'] ?? [], $request->input('play_order', []));

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'status' => $ad->status->value,
            ])
            ->log('Updated ad');

        return redirect()
            ->route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id])
            ->with('success', __('Ad updated successfully.'));
    }

    public function destroy(string $lang, Ad $ad): RedirectResponse
    {
        $filePath = $ad->file_path;

        activity()
            ->performedOn($ad)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['id' => $ad->id])
            ->log('Deleted ad');

        $ad->screens()->detach();
        $ad->delete();

        if ($filePath) {
            $this->fileService->deleteFile(basename($filePath), Ad::UPLOAD_FOLDER);
        }

        return redirect()
            ->route('admin.ads.index', ['lang' => $lang])
            ->with('success', __('Ad deleted successfully.'));
    }

    private function availableStatuses(): array
    {
        return collect(AdStatus::cases())
            ->mapWithKeys(fn (AdStatus $status) => [$status->value => ucfirst($status->value)])
            ->toArray();
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
    }

    private function determineFileType(?UploadedFile $file, ?string $path = null): string
    {
        $extension = null;

        if ($file) {
            $extension = strtolower($file->getClientOriginalExtension());
        } elseif ($path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        return match (true) {
            in_array($extension, ['mp4', 'm4v', 'mov', 'avi', 'wmv', 'mkv', 'webm', 'mpeg'], true) => 'video',
            $extension === 'gif' => 'gif',
            default => 'image',
        };
    }
}
