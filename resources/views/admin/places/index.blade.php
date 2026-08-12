@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.places.index', ['lang' => $lang]);

        // Same fallback idiom as the migrated logs module: Spatie returns an empty
        // string (not null) for a missing translation, so `?:` is required for the
        // placeholder to actually appear.
        $placeName = fn ($place) => data_get($place->getTranslations('name'), app()->getLocale())
            ?: __('admin.places.unnamed', ['id' => $place->id]);

        $placeAddress = fn ($place) => data_get($place->getTranslations('address'), app()->getLocale()) ?: null;

        // The view maps the stored value to a label; the stored enum values themselves
        // are never rewritten, and an unknown value still renders safely.
        $typeLabel = fn (?string $type) => $type
            ? \App\Support\Lang::t('admin.places.types.' . $type, ucfirst($type))
            : null;
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.places.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.places')],
            ],
            'primaryAction' => auth('admin')->user()?->can('places.create')
                ? [
                    'href' => route('admin.places.create', ['lang' => $lang]),
                    'label' => __('admin.table.new'),
                    'icon' => 'plus',
                ]
                : null,
        ])

        {{-- Filter card: query parameter names (`search`, `type`) are preserved verbatim. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.places.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row">
                        <div class="col-md-8 col-lg-6">
                            <div class="form-group">
                                <label for="search">{{ __('admin.places.filters.search') }}</label>
                                <input type="text"
                                       id="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="{{ __('admin.places.filters.search_placeholder') }}"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <div class="form-group">
                                <label for="type">{{ __('admin.places.filters.type') }}</label>
                                <select id="type" name="type" class="form-control">
                                    <option value="">{{ __('admin.places.filters.all_types') }}</option>
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>
                                            {{ \App\Support\Lang::t('admin.places.types.' . $value, $label) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <x-admin.group-btn class="justify-content-end">
                                <x-admin.btn type="submit" variant="primary" icon="filter">
                                    {{ __('admin.buttons.filter') }}
                                </x-admin.btn>
                                <x-admin.btn :href="$indexUrl" variant="light" icon="rotate-ccw">
                                    {{ __('admin.buttons.reset') }}
                                </x-admin.btn>
                            </x-admin.group-btn>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.places.stats.total') }}</span>
                        <span class="admin-stat-value">{{ $stats['total'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.places.stats.with_screens') }}</span>
                        <span class="admin-stat-value text-primary">{{ $stats['with_screens'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.results-summary', [
            'data' => $places,
            'label' => __('admin.places.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.places.table.name') }}</th>
                                <th scope="col">{{ __('admin.places.table.type') }}</th>
                                <th scope="col">{{ __('admin.places.table.address') }}</th>
                                <th scope="col">{{ __('admin.places.table.screens_count') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($places as $place)
                                <tr>
                                    <th scope="row">{{ ($places->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>{{ $placeName($place) }}</td>
                                    <td>
                                        @if ($place->type)
                                            <x-admin.badge variant="light">
                                                {{ $typeLabel($place->type->value) }}
                                            </x-admin.badge>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $placeAddress($place) ?? '—' }}</td>
                                    <td>
                                        <x-admin.badge :variant="$place->screens_count > 0 ? 'primary' : 'secondary'" pill>
                                            {{ $place->screens_count }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('places.view')
                                                <x-admin.btn
                                                    :href="route('admin.places.show', ['lang' => $lang, 'place' => $place->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.places.actions.view') }}
                                                </x-admin.btn>
                                            @endcan
                                            @can('places.edit')
                                                <x-admin.btn
                                                    :href="route('admin.places.edit', ['lang' => $lang, 'place' => $place->id])"
                                                    variant="outline-primary"
                                                    size="sm"
                                                    icon="edit-2">
                                                    {{ __('admin.places.actions.edit') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 6,
                                    'message' => __('admin.places.table.empty'),
                                    'icon' => 'map-pin',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $places, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
