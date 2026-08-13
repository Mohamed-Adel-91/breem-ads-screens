@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.screens.index', ['lang' => $lang]);

        $placeName = fn ($place) => $place
            ? (data_get($place->getTranslations('name'), app()->getLocale())
                ?: __('admin.screens.unnamed_place', ['id' => $place->id]))
            : null;
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.screens.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.screens')],
            ],
            'primaryAction' => auth('admin')->user()?->can('screens.create')
                ? [
                    'href' => route('admin.screens.create', ['lang' => $lang]),
                    'label' => __('admin.table.new'),
                    'icon' => 'plus',
                ]
                : null,
        ])

        {{-- Filter card: query parameter names (`search`, `status`, `place_id`) preserved verbatim. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.screens.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="search">{{ __('admin.screens.filters.search') }}</label>
                                <input type="text"
                                       id="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="{{ __('admin.screens.filters.search_placeholder') }}"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="status">{{ __('admin.screens.filters.status') }}</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">{{ __('admin.screens.filters.all_statuses') }}</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                            {{ \App\Support\Lang::t('admin.screens.statuses.' . $value, $label) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="place_id">{{ __('admin.screens.filters.place') }}</label>
                                {{-- Native Bootstrap select: the place list is modest, so no Select2 is loaded. --}}
                                <select id="place_id" name="place_id" class="form-control">
                                    <option value="">{{ __('admin.screens.filters.all_places') }}</option>
                                    @foreach ($places as $place)
                                        <option value="{{ $place->id }}"
                                            @selected(($filters['place_id'] ?? '') == $place->id)>
                                            {{ $placeName($place) }}
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

        {{-- Counters come straight from the controller; nothing is recalculated in Blade. --}}
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.screens.stats.total') }}</span>
                        <span class="admin-stat-value">{{ localized_digits($stats['total'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.screens.stats.online') }}</span>
                        <span class="admin-stat-value text-success">{{ localized_digits($stats['online'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.screens.stats.offline') }}</span>
                        <span class="admin-stat-value text-danger">{{ localized_digits($stats['offline'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.screens.stats.maintenance') }}</span>
                        <span class="admin-stat-value text-warning">{{ localized_digits($stats['maintenance'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.results-summary', [
            'data' => $screens,
            'label' => __('admin.screens.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.screens.table.code') }}</th>
                                <th scope="col">{{ __('admin.screens.table.place') }}</th>
                                <th scope="col">{{ __('admin.screens.table.status') }}</th>
                                <th scope="col">{{ __('admin.screens.table.device_uid') }}</th>
                                <th scope="col">{{ __('admin.screens.table.active_schedules') }}</th>
                                <th scope="col">{{ __('admin.screens.table.last_heartbeat') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($screens as $screen)
                                <tr>
                                    <th scope="row">{{ ($screens->firstItem() ?? 1) + $loop->index }}</th>
                                    <td><code>{{ $screen->code }}</code></td>
                                    <td>{{ $placeName($screen->place) ?? '—' }}</td>
                                    <td>
                                        @include('admin.screens.partials.status-badge', ['status' => $screen->status])
                                    </td>
                                    <td>
                                        @if ($screen->device_uid)
                                            <code>{{ $screen->device_uid }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $screen->active_schedule_count }}</td>
                                    <td>
                                        @include('admin.screens.partials.heartbeat', ['heartbeat' => $screen->last_heartbeat])
                                    </td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('screens.view')
                                                <x-admin.btn
                                                    :href="route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.screens.actions.view') }}
                                                </x-admin.btn>
                                            @endcan
                                            @can('screens.edit')
                                                <x-admin.btn
                                                    :href="route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id])"
                                                    variant="outline-primary"
                                                    size="sm"
                                                    icon="edit-2">
                                                    {{ __('admin.screens.actions.edit') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 8,
                                    'message' => __('admin.screens.table.empty'),
                                    'icon' => 'monitor',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $screens, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
