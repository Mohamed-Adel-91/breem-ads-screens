@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $locale = app()->getLocale();
        $adsIndexUrl = route('admin.ads.index', ['lang' => $lang]);
        $overviewUrl = route('admin.ads.schedules.overview', ['lang' => $lang]);

        $adTitle = function ($ad) use ($locale) {
            if (!$ad) {
                return null;
            }

            return data_get($ad->getTranslations('title'), $locale)
                ?: __('admin.ads.untitled', ['id' => $ad->id]);
        };

        $placeName = fn ($place) => $place
            ? (data_get($place->getTranslations('name'), $locale) ?: null)
            : null;

        // State badge colours live here, next to the markup that uses them; the
        // state value itself is computed by AdSchedule::currentState().
        $stateVariants = [
            \App\Models\AdSchedule::STATE_CURRENT => 'success',
            \App\Models\AdSchedule::STATE_UPCOMING => 'info',
            \App\Models\AdSchedule::STATE_ENDED => 'secondary',
            \App\Models\AdSchedule::STATE_INACTIVE => 'danger',
        ];
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => __('admin.schedules.overview.heading'),
            'subtitle' => __('admin.schedules.overview.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads'), 'url' => $adsIndexUrl],
                ['label' => __('admin.sidebar.ads_system_schedules')],
            ],
        ])

        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.schedules.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $overviewUrl }}">
                    <div class="row">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="ad_id">{{ __('admin.schedules.filters.ad') }}</label>
                                <select id="ad_id" name="ad_id" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all_ads') }}</option>
                                    @foreach ($availableAds as $option)
                                        <option value="{{ $option->id }}"
                                            @selected(($filters['ad_id'] ?? '') == $option->id)>
                                            {{ $adTitle($option) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="screen_id">{{ __('admin.schedules.filters.screen') }}</label>
                                <select id="screen_id" name="screen_id" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all_screens') }}</option>
                                    @foreach ($availableScreens as $option)
                                        <option value="{{ $option->id }}"
                                            @selected(($filters['screen_id'] ?? '') == $option->id)>
                                            {{ $option->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="place_id">{{ __('admin.schedules.filters.place') }}</label>
                                <select id="place_id" name="place_id" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all_places') }}</option>
                                    @foreach ($availablePlaces as $option)
                                        <option value="{{ $option->id }}"
                                            @selected(($filters['place_id'] ?? '') == $option->id)>
                                            {{ $placeName($option) ?: __('admin.schedules.overview.unnamed_place', ['id' => $option->id]) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="state">{{ __('admin.schedules.filters.state') }}</label>
                                <select id="state" name="state" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all') }}</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state }}" @selected(($filters['state'] ?? '') === $state)>
                                            {{ __('admin.schedules.states.' . $state) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="from_date">{{ __('admin.schedules.filters.from_date') }}</label>
                                <input type="date"
                                       id="from_date"
                                       name="from_date"
                                       dir="ltr"
                                       value="{{ $filters['from_date'] ?? '' }}"
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="to_date">{{ __('admin.schedules.filters.to_date') }}</label>
                                <input type="date"
                                       id="to_date"
                                       name="to_date"
                                       dir="ltr"
                                       value="{{ $filters['to_date'] ?? '' }}"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="col-12">
                            <x-admin.group-btn class="justify-content-end">
                                <a href="{{ $overviewUrl }}" class="btn btn-light">
                                    <i class="fe fe-rotate-ccw" aria-hidden="true"></i>
                                    <span>{{ __('admin.schedules.overview.reset') }}</span>
                                </a>
                                <x-admin.btn type="submit" icon="filter">
                                    {{ __('admin.schedules.overview.apply') }}
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
                        <span class="admin-stat-label">{{ __('admin.schedules.stats.total') }}</span>
                        <span class="admin-stat-value">{{ localized_digits($stats['total'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.states.current') }}</span>
                        <span class="admin-stat-value text-success">{{ localized_digits($stats['current'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.states.upcoming') }}</span>
                        <span class="admin-stat-value text-info">{{ localized_digits($stats['upcoming'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.states.inactive') }}</span>
                        <span class="admin-stat-value text-danger">{{ localized_digits($stats['inactive'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.results-summary', [
            'data' => $schedules,
            'label' => __('admin.schedules.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.schedules.table.ad') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.screen') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.start_time') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.end_time') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.state') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules as $schedule)
                                @php($state = $schedule->currentState())
                                <tr>
                                    <td>
                                        @if ($schedule->ad)
                                            <span class="d-block font-weight-bold">{{ $adTitle($schedule->ad) }}</span>
                                            <small class="text-muted">#{{ $schedule->ad->id }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold">
                                            {{ $schedule->screen?->code ?? __('admin.ads.show.screen_removed') }}
                                        </span>
                                        @if ($placeName($schedule->screen?->place))
                                            <small class="text-muted">{{ $placeName($schedule->screen->place) }}</small>
                                        @endif
                                    </td>
                                    <td dir="ltr">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td dir="ltr">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <x-admin.badge :variant="$stateVariants[$state] ?? 'secondary'">
                                            {{ __('admin.schedules.states.' . $state) }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        @if ($schedule->ad)
                                            <x-admin.group-btn>
                                                <a href="{{ route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $schedule->ad->id]) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fe fe-calendar" aria-hidden="true"></i>
                                                    <span>{{ __('admin.schedules.overview.manage') }}</span>
                                                </a>
                                            </x-admin.group-btn>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 6,
                                    'message' => __('admin.schedules.table.empty'),
                                    'icon' => 'calendar',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $schedules, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
