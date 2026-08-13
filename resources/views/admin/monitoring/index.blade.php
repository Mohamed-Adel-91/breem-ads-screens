@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.monitoring.index', ['lang' => $lang]);

        $placeName = fn ($place) => $place
            ? (data_get($place->getTranslations('name'), app()->getLocale())
                ?: __('admin.monitoring.unnamed_place', ['id' => $place->id]))
            : null;
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.monitoring.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_monitoring')],
            ],
        ])

        {{-- Filter card: query parameter names (`search`, `status`, `place_id`,
             `has_alerts`) are preserved verbatim. No new backend filter is offered. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.monitoring.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="search">{{ __('admin.monitoring.filters.search') }}</label>
                                <input type="text"
                                       id="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="{{ __('admin.monitoring.filters.search_placeholder') }}"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="status">{{ __('admin.monitoring.filters.status') }}</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">{{ __('admin.monitoring.filters.all_statuses') }}</option>
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
                                <label for="place_id">{{ __('admin.monitoring.filters.place') }}</label>
                                <select id="place_id" name="place_id" class="form-control">
                                    <option value="">{{ __('admin.monitoring.filters.all_places') }}</option>
                                    @foreach ($places as $place)
                                        <option value="{{ $place->id }}"
                                            @selected(($filters['place_id'] ?? '') == $place->id)>
                                            {{ $placeName($place) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="has_alerts"
                                           name="has_alerts"
                                           value="1"
                                           @checked($filters['has_alerts'] ?? false)>
                                    <label class="custom-control-label" for="has_alerts">
                                        {{ __('admin.monitoring.filters.has_alerts') }}
                                    </label>
                                </div>
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

        {{-- Counters come straight from the controller; Blade never derives status. --}}
        <div class="row">
            @foreach ($summary as $status => $count)
                <div class="col-sm-6 col-lg-4">
                    <div class="card admin-stat-card mb-4">
                        <div class="card-body">
                            <span class="admin-stat-label">
                                {{ \App\Support\Lang::t('admin.screens.statuses.' . $status, ucfirst($status)) }}
                            </span>
                            <span @class([
                                'admin-stat-value',
                                'text-success' => $status === 'online',
                                'text-danger' => $status === 'offline',
                                'text-warning' => $status === 'maintenance',
                            ])>{{ localized_digits($count) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @include('admin.partials.results-summary', [
            'data' => $screens,
            'label' => __('admin.monitoring.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.monitoring.table.code') }}</th>
                                <th scope="col">{{ __('admin.monitoring.table.place') }}</th>
                                <th scope="col">{{ __('admin.monitoring.table.status') }}</th>
                                <th scope="col">{{ __('admin.monitoring.table.last_report') }}</th>
                                <th scope="col">{{ __('admin.monitoring.table.offline_logs') }}</th>
                                <th scope="col">{{ __('admin.monitoring.table.active_schedules') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($screens as $screen)
                                {{-- latestLog is a latestOfMany relation, so every row gets its own. --}}
                                @php($latestLog = $screen->latestLog)
                                <tr>
                                    <th scope="row">{{ ($screens->firstItem() ?? 1) + $loop->index }}</th>
                                    <td><code>{{ $screen->code }}</code></td>
                                    <td>{{ $placeName($screen->place) ?? '—' }}</td>
                                    <td>
                                        {{-- Reuses the Screens status badge: one semantic mapping project-wide. --}}
                                        @include('admin.screens.partials.status-badge', ['status' => $screen->status])
                                    </td>
                                    <td>
                                        @if ($latestLog)
                                            @include('admin.screens.partials.status-badge', ['status' => $latestLog->status])
                                            <small class="d-block text-muted" dir="ltr">
                                                {{ optional($latestLog->reported_at)->format('Y-m-d H:i') ?? '—' }}
                                            </small>
                                        @else
                                            <span class="text-muted">{{ __('admin.monitoring.table.no_logs') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-admin.badge :variant="$screen->offline_logs_count > 0 ? 'danger' : 'secondary'" pill>
                                            {{ $screen->offline_logs_count }}
                                        </x-admin.badge>
                                    </td>
                                    <td>{{ $screen->active_schedule_count }}</td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('monitoring.view')
                                                <x-admin.btn
                                                    :href="route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.monitoring.actions.view') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 8,
                                    'message' => __('admin.monitoring.table.empty'),
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
