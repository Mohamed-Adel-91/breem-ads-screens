@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.logs.index', ['lang' => $lang]);
        $queryParams = collect($filters)->filter(fn ($value) => filled($value))->all();

        $placeName = function ($place) {
            if (!$place) {
                return null;
            }

            return data_get($place->getTranslations('name'), app()->getLocale())
                ?: __('admin.logs.unnamed_place', ['id' => $place->id]);
        };

        $statusVariant = function (?string $status): string {
            return match ($status) {
                'online', 'active' => 'success',
                'offline', 'inactive' => 'danger',
                'idle', 'pending' => 'warning',
                default => 'secondary',
            };
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.logs')],
            ],
        ])

        {{-- Filter card: every existing query parameter name is preserved verbatim. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.logs.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="screen_status">{{ __('admin.logs.filters.screen_status') }}</label>
                                <select id="screen_status" name="screen_status" class="form-control">
                                    <option value="">{{ __('admin.logs.filters.all_statuses') }}</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected(($filters['screen_status'] ?? '') === $value)>
                                            {{ ucfirst($label) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="screen_id">{{ __('admin.logs.filters.screen') }}</label>
                                <select id="screen_id" name="screen_id" class="form-control">
                                    <option value="">{{ __('admin.logs.filters.all_screens') }}</option>
                                    @foreach ($screens as $screen)
                                        <option value="{{ $screen->id }}"
                                            @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                            {{ $screen->code }}@if ($screen->place) — {{ $placeName($screen->place) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="from_date">{{ __('admin.logs.filters.from_date') }}</label>
                                <input type="date"
                                       id="from_date"
                                       name="from_date"
                                       class="form-control"
                                       value="{{ $filters['from_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="to_date">{{ __('admin.logs.filters.to_date') }}</label>
                                <input type="date"
                                       id="to_date"
                                       name="to_date"
                                       class="form-control"
                                       value="{{ $filters['to_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="ad_id">{{ __('admin.logs.filters.ad') }}</label>
                                <select id="ad_id" name="ad_id" class="form-control">
                                    <option value="">{{ __('admin.logs.filters.all_ads') }}</option>
                                    @foreach ($ads as $ad)
                                        <option value="{{ $ad->id }}" @selected(($filters['ad_id'] ?? '') == $ad->id)>
                                            {{ data_get($ad->getTranslations('title'), app()->getLocale())
                                                ?: __('admin.logs.unnamed_ad', ['id' => $ad->id]) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="played_from">{{ __('admin.logs.filters.played_from') }}</label>
                                <input type="date"
                                       id="played_from"
                                       name="played_from"
                                       class="form-control"
                                       value="{{ $filters['played_from'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="played_to">{{ __('admin.logs.filters.played_to') }}</label>
                                <input type="date"
                                       id="played_to"
                                       name="played_to"
                                       class="form-control"
                                       value="{{ $filters['played_to'] ?? '' }}">
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

        @can('logs.export')
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.logs.exports.heading') }}</h2>
                </div>
                <div class="card-body">
                    <x-admin.group-btn>
                        <x-admin.btn
                            :href="route('admin.logs.download', ['lang' => $lang, 'type' => 'system'])"
                            variant="outline-secondary"
                            size="sm"
                            icon="download">
                            {{ __('admin.logs.exports.system') }}
                        </x-admin.btn>
                        <x-admin.btn
                            :href="route('admin.logs.download', array_merge(['lang' => $lang, 'type' => 'screen'], $queryParams))"
                            variant="outline-secondary"
                            size="sm"
                            icon="download">
                            {{ __('admin.logs.exports.screen') }}
                        </x-admin.btn>
                        <x-admin.btn
                            :href="route('admin.logs.download', array_merge(['lang' => $lang, 'type' => 'playback'], $queryParams))"
                            variant="outline-secondary"
                            size="sm"
                            icon="download">
                            {{ __('admin.logs.exports.playback') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>
            </div>
        @endcan

        {{-- Screen status logs — independent paginator: screen_page --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.logs.screen_logs.heading') }}</h2>
                <x-admin.badge variant="light">
                    {{ $screenLogs->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.logs.filters.screen') }}</th>
                                <th scope="col">{{ __('admin.logs.screen_logs.place') }}</th>
                                <th scope="col">{{ __('admin.logs.screen_logs.status') }}</th>
                                <th scope="col">{{ __('admin.logs.screen_logs.reported_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($screenLogs as $log)
                                <tr>
                                    <td>{{ $log->screen?->code ?? '—' }}</td>
                                    <td>{{ $placeName($log->screen?->place) ?? '—' }}</td>
                                    <td>
                                        @php $statusValue = $log->status->value ?? null; @endphp
                                        @if ($statusValue)
                                            <x-admin.badge :variant="$statusVariant($statusValue)">
                                                {{ ucfirst($statusValue) }}
                                            </x-admin.badge>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 4,
                                    'message' => __('admin.logs.screen_logs.empty'),
                                    'icon' => 'monitor',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $screenLogs, 'variant' => 'static'])
            </div>
        </div>

        {{-- Playback logs — independent paginator: playback_page --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.logs.playback_logs.heading') }}</h2>
                <x-admin.badge variant="light">
                    {{ $playbackLogs->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.logs.filters.screen') }}</th>
                                <th scope="col">{{ __('admin.logs.filters.ad') }}</th>
                                <th scope="col">{{ __('admin.logs.playback_logs.played_at') }}</th>
                                <th scope="col">{{ __('admin.logs.playback_logs.duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($playbackLogs as $log)
                                <tr>
                                    <td>{{ $log->screen?->code ?? '—' }}</td>
                                    <td>
                                        {{ $log->ad
                                            ? (data_get($log->ad->getTranslations('title'), app()->getLocale())
                                                ?: __('admin.logs.unnamed_ad', ['id' => $log->ad->id]))
                                            : '—' }}
                                    </td>
                                    <td>{{ optional($log->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>{{ $log->duration }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 4,
                                    'message' => __('admin.logs.playback_logs.empty'),
                                    'icon' => 'play-circle',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $playbackLogs, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
