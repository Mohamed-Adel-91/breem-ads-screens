@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.ads.index', ['lang' => $lang]);

        // Spatie returns an empty string (not null) for a missing translation, so
        // `?:` is required for the placeholder to actually appear.
        $adTitle = fn ($ad) => data_get($ad->getTranslations('title'), app()->getLocale())
            ?: __('admin.ads.untitled', ['id' => $ad->id]);

        $screenLabel = function ($screen) {
            $label = $screen->code;

            if ($screen->place) {
                $placeName = data_get($screen->place->getTranslations('name'), app()->getLocale());

                if ($placeName) {
                    $label .= ' — ' . $placeName;
                }
            }

            return $label;
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.ads.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads')],
            ],
            'primaryAction' => auth('admin')->user()?->can('ads.create')
                ? [
                    'href' => route('admin.ads.create', ['lang' => $lang]),
                    'label' => __('admin.buttons.new'),
                    'icon' => 'plus',
                ]
                : null,
        ])

        {{-- Filter card: query parameter names (`search`, `status`, `screen_id`,
             `from_date`, `to_date`) are preserved verbatim. No filter that the
             controller does not already support is offered. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.ads.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="search">{{ __('admin.ads.filters.search') }}</label>
                                <input type="text"
                                       id="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="{{ __('admin.ads.filters.search_placeholder') }}"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label for="status">{{ __('admin.ads.filters.status') }}</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">{{ __('admin.ads.filters.all_statuses') }}</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                            {{ \App\Support\Lang::t('admin.ads.statuses.' . $value, $label) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="screen_id">{{ __('admin.ads.filters.screen') }}</label>
                                <select id="screen_id" name="screen_id" class="form-control">
                                    <option value="">{{ __('admin.ads.filters.all_screens') }}</option>
                                    @foreach ($screens as $screen)
                                        <option value="{{ $screen->id }}"
                                            @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                            {{ $screenLabel($screen) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="from_date">{{ __('admin.ads.filters.from_date') }}</label>
                                <input type="date"
                                       id="from_date"
                                       name="from_date"
                                       class="form-control"
                                       value="{{ $filters['from_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="to_date">{{ __('admin.ads.filters.to_date') }}</label>
                                <input type="date"
                                       id="to_date"
                                       name="to_date"
                                       class="form-control"
                                       value="{{ $filters['to_date'] ?? '' }}">
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

        {{-- Counters come straight from the controller; nothing is recalculated here. --}}
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.ads.stats.total') }}</span>
                        <span class="admin-stat-value">{{ $stats['total'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.ads.stats.active') }}</span>
                        <span class="admin-stat-value text-success">{{ $stats['active'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.ads.stats.pending') }}</span>
                        <span class="admin-stat-value text-warning">{{ $stats['pending'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.ads.stats.expired') }}</span>
                        <span class="admin-stat-value text-danger">{{ $stats['expired'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.results-summary', [
            'data' => $ads,
            'label' => __('admin.ads.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.ads.table.title') }}</th>
                                <th scope="col">{{ __('admin.ads.table.status') }}</th>
                                <th scope="col">{{ __('admin.ads.table.media') }}</th>
                                <th scope="col">{{ __('admin.ads.table.screens') }}</th>
                                <th scope="col">{{ __('admin.ads.table.schedules') }}</th>
                                <th scope="col">{{ __('admin.ads.table.start_date') }}</th>
                                <th scope="col">{{ __('admin.ads.table.end_date') }}</th>
                                <th scope="col">{{ __('admin.ads.table.owner') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ads as $ad)
                                <tr>
                                    <th scope="row">{{ ($ads->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>
                                        <span class="d-block font-weight-bold">{{ $adTitle($ad) }}</span>
                                        <small class="text-muted">{{ __('admin.ads.table.id') }}: {{ $ad->id }}</small>
                                    </td>
                                    <td>
                                        @include('admin.ads.partials.status-badge', ['status' => $ad->status])
                                    </td>
                                    <td>
                                        <x-admin.badge variant="light">
                                            {{ \App\Support\Lang::t('admin.ads.file_types.' . $ad->file_type, ucfirst((string) $ad->file_type)) }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        <x-admin.badge :variant="$ad->screens->count() > 0 ? 'primary' : 'secondary'" pill>
                                            {{ $ad->screens->count() }}
                                        </x-admin.badge>
                                    </td>
                                    {{-- Counted in SQL via withCount('schedules') — no lazy query per row. --}}
                                    <td>{{ $ad->schedules_count }}</td>
                                    <td>{{ optional($ad->start_date)->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ optional($ad->end_date)->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $ad->creator?->name ?? '—' }}</td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('ads.view')
                                                <x-admin.btn
                                                    :href="route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.ads.actions.view') }}
                                                </x-admin.btn>
                                            @endcan
                                            @can('ads.edit')
                                                <x-admin.btn
                                                    :href="route('admin.ads.edit', ['lang' => $lang, 'ad' => $ad->id])"
                                                    variant="outline-primary"
                                                    size="sm"
                                                    icon="edit-2">
                                                    {{ __('admin.ads.actions.edit') }}
                                                </x-admin.btn>
                                            @endcan
                                            @can('ads.delete')
                                                <x-admin.btn
                                                    :href="route('admin.ads.destroy', ['lang' => $lang, 'ad' => $ad->id])"
                                                    method="DELETE"
                                                    variant="outline-danger"
                                                    size="sm"
                                                    icon="trash-2"
                                                    :confirm="__('admin.ads.actions.delete_confirm')">
                                                    {{ __('admin.ads.actions.delete') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 10,
                                    'message' => __('admin.ads.table.empty'),
                                    'icon' => 'film',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $ads, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
