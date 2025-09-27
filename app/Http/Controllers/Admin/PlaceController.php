<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlaceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Places\StorePlaceRequest;
use App\Http\Requests\Admin\Places\UpdatePlaceRequest;
use App\Models\Place;
use App\Support\Lang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaceController extends Controller
{
    public function index(string $lang, Request $request): View
    {
        $query = Place::query()->withCount('screens')->with('screens');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->ar', 'like', "%{$search}%")
                    ->orWhere('address->en', 'like', "%{$search}%")
                    ->orWhere('address->ar', 'like', "%{$search}%");
            });
        }

        $places = $query->paginate(20)->withQueryString();

        return view('admin.places.index', [
            'pageName' => Lang::t('admin.pages.places.index', 'الأماكن'),
            'lang' => $lang,
            'places' => $places,
            'filters' => [
                'type' => $request->input('type'),
                'search' => $request->input('search'),
            ],
            'types' => $this->availableTypes(),
            'stats' => [
                'total' => Place::count(),
                'with_screens' => Place::has('screens')->count(),
            ],
        ]);
    }

    public function create(string $lang): View
    {
        $place = new Place([
            'type' => PlaceType::Cafe,
        ]);

        return view('admin.places.create', [
            'pageName' => Lang::t('admin.pages.places.create', 'إضافة مكان جديد'),
            'lang' => $lang,
            'place' => $place,
            'types' => $this->availableTypes(),
        ]);
    }

    public function store(string $lang, StorePlaceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $place = Place::create([
            'name' => $this->prepareTranslations($data['name']),
            'address' => $this->prepareTranslations($data['address'] ?? []),
            'type' => $data['type'],
        ]);

        activity()
            ->performedOn($place)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['place_id' => $place->id])
            ->log('Created place');

        return redirect()
            ->route('admin.places.show', ['lang' => $lang, 'place' => $place->id])
            ->with('success', Lang::t('admin.flash.places.created', 'Place created successfully.'));
    }

    public function show(string $lang, Place $place): View
    {
        $place->load(['screens' => fn ($builder) => $builder->withCount(['schedules', 'ads'])]);

        return view('admin.places.show', [
            'pageName' => Lang::t('admin.pages.places.show', 'تفاصيل المكان'),
            'lang' => $lang,
            'place' => $place,
        ]);
    }

    public function edit(string $lang, Place $place): View
    {
        return view('admin.places.edit', [
            'pageName' => Lang::t('admin.pages.places.edit', 'تعديل المكان'),
            'lang' => $lang,
            'place' => $place,
            'types' => $this->availableTypes(),
        ]);
    }

    public function update(string $lang, UpdatePlaceRequest $request, Place $place): RedirectResponse
    {
        $data = $request->validated();

        $place->update([
            'name' => $this->prepareTranslations($data['name']),
            'address' => $this->prepareTranslations($data['address'] ?? []),
            'type' => $data['type'],
        ]);

        activity()
            ->performedOn($place)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['place_id' => $place->id])
            ->log('Updated place');

        return redirect()
            ->route('admin.places.show', ['lang' => $lang, 'place' => $place->id])
            ->with('success', Lang::t('admin.flash.places.updated', 'Place updated successfully.'));
    }

    public function destroy(string $lang, Place $place): RedirectResponse
    {
        if ($place->screens()->exists()) {
            return redirect()
                ->route('admin.places.show', ['lang' => $lang, 'place' => $place->id])
                ->with('error', Lang::t('admin.flash.places.cannot_delete_with_screens', 'Cannot delete a place while it still has screens attached.'));
        }

        $placeId = $place->id;
        $place->delete();

        activity()
            ->performedOn($place)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['place_id' => $placeId])
            ->log('Deleted place');

        return redirect()
            ->route('admin.places.index', ['lang' => $lang])
            ->with('success', Lang::t('admin.flash.places.deleted', 'Place deleted successfully.'));
    }

    private function availableTypes(): array
    {
        return collect(PlaceType::cases())
            ->mapWithKeys(fn (PlaceType $type) => [$type->value => ucfirst($type->value)])
            ->toArray();
    }

    private function prepareTranslations(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->toArray();
    }
}
