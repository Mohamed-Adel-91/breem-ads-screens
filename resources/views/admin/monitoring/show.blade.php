@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.monitoring.index', ['lang' => $lang]);

        $placeName = $screen->place
            ? (data_get($screen->place->getTranslations('name'), app()->getLocale())
                ?: __('admin.monitoring.unnamed_place', ['id' => $screen->place->id]))
            : null;

        $adTitle = fn ($ad, $fallbackId = null) => $ad
            ? (data_get($ad->getTranslations('title'), app()->getLocale())
                ?: __('admin.monitoring.unnamed_ad', ['id' => $ad->id]))
            : __('admin.monitoring.unnamed_ad', ['id' => $fallbackId]);

        // Everything below is already eager-loaded / computed by
        // MonitoringController::showScreen. No domain query runs in this view, and
        // no operational status is derived here — the stored column is authoritative.
        $activeScheduleCount = $screen->schedules->where('is_active', true)->count();
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $screen->code,
            'subtitle' => $placeName ?: __('admin.monitoring.show_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_monitoring'), 'url' => $indexUrl],
                ['label' => $screen->code],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.monitoring.actions.back_to_index'),
                'icon' => 'arrow-left',
            ],
        ])

        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.monitoring.show.status') }}</span>
                        <span class="d-block mt-2">
                            @include('admin.screens.partials.status-badge', ['status' => $screen->status])
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.monitoring.show.last_heartbeat') }}</span>
                        <span class="d-block mt-2" dir="ltr">
                            {{-- Reuses the Screens heartbeat partial: presentation only,
                                 heartbeat state is never mutated from a view. --}}
                            @include('admin.screens.partials.heartbeat', ['heartbeat' => $screen->last_heartbeat])
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        {{-- Real elapsed-time availability from ScreenAvailabilityService,
                             not the old online-log-count ratio. --}}
                        <span class="admin-stat-label">{{ __('admin.monitoring.show.availability') }}</span>
                        <span class="admin-stat-value">
                            {{ $availability['availability'] !== null ? $availability['availability'] . '%' : '—' }}
                        </span>
                        <small class="d-block text-muted mt-1">
                            {{ $availability['availability'] !== null
                                ? __('admin.monitoring.show.availability_hint')
                                : __('admin.monitoring.show.availability_empty') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.monitoring.show.active_schedules') }}</span>
                        <span class="admin-stat-value">{{ $activeScheduleCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.monitoring.table.place') }}</th>
                                        <td>{{ $placeName ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.monitoring.show.device_uid') }}</th>
                                        <td>
                                            @if ($screen->device_uid)
                                                <code class="admin-wrap-anywhere">{{ $screen->device_uid }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.monitoring.show.total_schedules') }}</th>
                                        <td>{{ $screen->schedules->count() }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.monitoring.show.attached_ads') }}</th>
                                        <td>{{ $screen->ads->count() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @can('monitoring.manage')
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="card-title mb-0">{{ __('admin.monitoring.acknowledge.heading') }}</h2>
                        </div>
                        <div class="card-body">
                            {{--
                                Acknowledging records that an administrator has SEEN the
                                alert. It no longer carries a status field: writing one
                                here let the dashboard manufacture connectivity, which is
                                exactly what Phase 11 removed. Connectivity comes from
                                device heartbeats; maintenance is set on the Screen edit
                                form.
                            --}}
                            @if ($openAlert)
                                <p class="text-muted small">
                                    {{ __('admin.monitoring.acknowledge.open_alert', [
                                        'time' => optional($openAlert->reported_at)->format('Y-m-d H:i'),
                                    ]) }}
                                </p>

                                <form method="POST"
                                      action="{{ route('admin.monitoring.screens.acknowledge', ['lang' => $lang, 'screen' => $screen->id]) }}"
                                      data-confirm-message="{{ __('admin.monitoring.acknowledge.confirm') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="note">{{ __('admin.monitoring.acknowledge.note') }}</label>
                                        <textarea id="note"
                                                  name="note"
                                                  rows="2"
                                                  placeholder="{{ __('admin.monitoring.acknowledge.note_placeholder') }}"
                                                  @class(['form-control', 'is-invalid' => $errors->has('note')])>{{ old('note') }}</textarea>
                                        @error('note')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <p class="text-muted small">{{ __('admin.monitoring.acknowledge.notice') }}</p>
                                    <x-admin.group-btn class="justify-content-end">
                                        <x-admin.btn type="submit" icon="check-circle">
                                            {{ __('admin.monitoring.acknowledge.submit') }}
                                        </x-admin.btn>
                                    </x-admin.group-btn>
                                </form>
                            @else
                                <p class="text-muted mb-0">{{ __('admin.monitoring.acknowledge.none_open') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.monitoring.show.schedules_heading') }}</h2>
                        <x-admin.badge variant="light">{{ $screen->schedules->count() }}</x-admin.badge>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.monitoring.show.ad') }}</th>
                                        <th scope="col">{{ __('admin.monitoring.show.start_time') }}</th>
                                        <th scope="col">{{ __('admin.monitoring.show.end_time') }}</th>
                                        <th scope="col">{{ __('admin.monitoring.show.active') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($screen->schedules as $schedule)
                                        <tr>
                                            <td>{{ $adTitle($schedule->ad, $schedule->ad_id) }}</td>
                                            <td dir="ltr">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td dir="ltr">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td>
                                                <x-admin.badge :variant="$schedule->is_active ? 'success' : 'secondary'">
                                                    {{ $schedule->is_active
                                                        ? __('admin.monitoring.show.yes')
                                                        : __('admin.monitoring.show.no') }}
                                                </x-admin.badge>
                                            </td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 4,
                                            'message' => __('admin.monitoring.show.schedules_empty'),
                                            'icon' => 'calendar',
                                        ])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.monitoring.show.ads_heading') }}</h2>
                        <x-admin.badge variant="light">{{ $screen->ads->count() }}</x-admin.badge>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.monitoring.show.play_order') }}</th>
                                        <th scope="col">{{ __('admin.monitoring.show.ad_title') }}</th>
                                        <th scope="col">{{ __('admin.monitoring.show.ad_status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($screen->ads as $ad)
                                        <tr>
                                            <td>{{ $ad->pivot->play_order }}</td>
                                            <td>{{ $adTitle($ad) }}</td>
                                            <td>
                                                {{-- Reuses the Ads status badge from Phase 6. --}}
                                                @include('admin.ads.partials.status-badge', ['status' => $ad->status])
                                            </td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.monitoring.show.ads_empty'),
                                            'icon' => 'film',
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
                <h2 class="card-title mb-0">{{ __('admin.monitoring.show.logs_heading') }}</h2>
                <x-admin.badge variant="light">
                    {{ $recentLogs->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.monitoring.show.status') }}</th>
                                <th scope="col">{{ __('admin.monitoring.show.reported_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td>
                                        @include('admin.screens.partials.status-badge', ['status' => $log->status])
                                    </td>
                                    <td dir="ltr">{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 2,
                                    'message' => __('admin.monitoring.show.logs_empty'),
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
                <h2 class="card-title mb-0">{{ __('admin.monitoring.show.playbacks_heading') }}</h2>
                <x-admin.badge variant="light">
                    {{ $recentPlaybacks->total() }} {{ __('admin.logs.entries') }}
                </x-admin.badge>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.monitoring.show.ad') }}</th>
                                <th scope="col">{{ __('admin.monitoring.show.played_at') }}</th>
                                <th scope="col">{{ __('admin.monitoring.show.duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPlaybacks as $playback)
                                <tr>
                                    <td>{{ $adTitle($playback->ad, $playback->ad_id) }}</td>
                                    <td dir="ltr">{{ optional($playback->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>{{ $playback->duration }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 3,
                                    'message' => __('admin.monitoring.show.playbacks_empty'),
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
