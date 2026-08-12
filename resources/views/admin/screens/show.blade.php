@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.screens.index', ['lang' => $lang]);

        $placeName = $screen->place
            ? (data_get($screen->place->getTranslations('name'), app()->getLocale())
                ?: __('admin.screens.unnamed_place', ['id' => $screen->place->id]))
            : null;

        $adTitle = fn ($ad, $fallbackId = null) => $ad
            ? (data_get($ad->getTranslations('title'), app()->getLocale())
                ?: __('admin.screens.unnamed_ad', ['id' => $ad->id]))
            : __('admin.screens.unnamed_ad', ['id' => $fallbackId]);

        // Everything below is already eager-loaded / computed by ScreenController::show.
        // No additional domain query is issued from this view.
        $activeScheduleCount = $screen->schedules->where('is_active', true)->count();
        $linkedAdsCount = $screen->ads->count();
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $screen->code,
            'subtitle' => __('admin.screens.show_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.screens'), 'url' => $indexUrl],
                ['label' => $screen->code],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.screens.actions.back_to_list'),
                'icon' => 'arrow-left',
            ],
            'primaryAction' => auth('admin')->user()?->can('screens.edit')
                ? [
                    'href' => route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]),
                    'label' => __('admin.screens.actions.edit'),
                    'icon' => 'edit-2',
                ]
                : null,
        ])

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.screens.show.identity') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.code') }}</th>
                                        <td><code>{{ $screen->code }}</code></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.place') }}</th>
                                        <td>
                                            @if ($screen->place)
                                                @can('places.view')
                                                    <a href="{{ route('admin.places.show', ['lang' => $lang, 'place' => $screen->place->id]) }}">
                                                        {{ $placeName }}
                                                    </a>
                                                @endcan
                                                @cannot('places.view')
                                                    {{ $placeName }}
                                                @endcannot
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.device_uid') }}</th>
                                        <td>
                                            @if ($screen->device_uid)
                                                <code>{{ $screen->device_uid }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.show.created_at') }}</th>
                                        <td>{{ optional($screen->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.show.updated_at') }}</th>
                                        <td>{{ optional($screen->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.screens.show.operational_status') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.status') }}</th>
                                        <td>
                                            @include('admin.screens.partials.status-badge', ['status' => $screen->status])
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.last_heartbeat') }}</th>
                                        <td>
                                            @include('admin.screens.partials.heartbeat', ['heartbeat' => $screen->last_heartbeat])
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.table.active_schedules') }}</th>
                                        <td>{{ $activeScheduleCount }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.show.linked_ads') }}</th>
                                        <td>{{ $linkedAdsCount }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.screens.show.uptime_heading') }}</h2>
                    </div>
                    <div class="card-body">
                        @if (!is_null($uptime))
                            <p class="admin-stat-value text-success mb-1">{{ $uptime }}%</p>
                            <p class="text-muted small">{{ __('admin.screens.show.uptime_help') }}</p>
                        @else
                            <p class="text-muted small">{{ __('admin.screens.show.uptime_empty') }}</p>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.show.online_events') }}</th>
                                        <td>{{ $logSummary['online'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.screens.show.offline_events') }}</th>
                                        <td>{{ $logSummary['offline'] ?? 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.screens.show.linked_ads') }}</h2>
                        <x-admin.badge variant="light">{{ $linkedAdsCount }}</x-admin.badge>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.screens.show.ad') }}</th>
                                        <th scope="col">{{ __('admin.screens.show.play_order') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($screen->ads as $ad)
                                        <tr>
                                            <td>{{ $adTitle($ad) }}</td>
                                            <td>{{ $ad->pivot->play_order }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 2,
                                            'message' => __('admin.screens.show.linked_ads_empty'),
                                            'icon' => 'film',
                                        ])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.screens.show.schedules') }}</h2>
                        <x-admin.badge variant="light">{{ $screen->schedules->count() }}</x-admin.badge>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.screens.show.ad') }}</th>
                                        <th scope="col">{{ __('admin.screens.show.schedule_window') }}</th>
                                        <th scope="col">{{ __('admin.screens.table.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($screen->schedules as $schedule)
                                        <tr>
                                            <td>{{ $adTitle($schedule->ad, $schedule->ad_id) }}</td>
                                            <td dir="ltr">
                                                {{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}
                                                &rarr;
                                                {{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}
                                            </td>
                                            <td>
                                                <x-admin.badge :variant="$schedule->is_active ? 'success' : 'secondary'">
                                                    {{ $schedule->is_active
                                                        ? __('admin.screens.show.active')
                                                        : __('admin.screens.show.inactive') }}
                                                </x-admin.badge>
                                            </td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.screens.show.schedules_empty'),
                                            'icon' => 'calendar',
                                        ])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Screen status logs — independent paginator: logs_page --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.screens.show.recent_logs') }}</h2>
                <x-admin.badge variant="light">
                    {{ $recentLogs->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.screens.table.status') }}</th>
                                <th scope="col">{{ __('admin.screens.show.reported_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td>
                                        @include('admin.screens.partials.status-badge', ['status' => $log->status])
                                    </td>
                                    <td>{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 2,
                                    'message' => __('admin.screens.show.logs_empty'),
                                    'icon' => 'activity',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $recentLogs, 'variant' => 'static'])
            </div>
        </div>

        {{-- Playback logs — independent paginator: playbacks_page --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.screens.show.recent_playbacks') }}</h2>
                <x-admin.badge variant="light">
                    {{ $recentPlaybacks->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.screens.show.ad') }}</th>
                                <th scope="col">{{ __('admin.screens.show.played_at') }}</th>
                                <th scope="col">{{ __('admin.screens.show.duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPlaybacks as $log)
                                <tr>
                                    <td>{{ $adTitle($log->ad, $log->ad_id) }}</td>
                                    <td>{{ optional($log->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>{{ $log->duration }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 3,
                                    'message' => __('admin.screens.show.playbacks_empty'),
                                    'icon' => 'play-circle',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $recentPlaybacks, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
