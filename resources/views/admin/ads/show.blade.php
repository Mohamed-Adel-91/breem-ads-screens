@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.ads.index', ['lang' => $lang]);

        $adTitle = data_get($ad->getTranslations('title'), app()->getLocale())
            ?: __('admin.ads.untitled', ['id' => $ad->id]);
        $adDescription = data_get($ad->getTranslations('description'), app()->getLocale()) ?: null;

        $screenLabel = fn ($screen) => $screen
            ? $screen->code
            : __('admin.ads.show.screen_removed');

        $placeName = function ($screen) {
            if (!$screen || !$screen->place) {
                return null;
            }

            return data_get($screen->place->getTranslations('name'), app()->getLocale()) ?: null;
        };

        // Everything below is already eager-loaded / computed by AdController::show
        // (screens.place, schedules.screen.place, creator, approver, playbacks.screen).
        // No additional domain query is issued from this view.
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $adTitle,
            'subtitle' => __('admin.ads.show_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads'), 'url' => $indexUrl],
                ['label' => $adTitle],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.ads.actions.back_to_list'),
                'icon' => 'arrow-left',
            ],
            'primaryAction' => auth('admin')->user()?->can('ads.edit')
                ? [
                    'href' => route('admin.ads.edit', ['lang' => $lang, 'ad' => $ad->id]),
                    'label' => __('admin.ads.actions.edit'),
                    'icon' => 'edit-2',
                ]
                : null,
        ])

        @can('ads.view')
            <div class="card mb-4">
                <div class="card-body">
                    <x-admin.group-btn>
                        <x-admin.btn
                            :href="route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id])"
                            variant="outline-primary"
                            size="sm"
                            icon="calendar">
                            {{ __('admin.ads.actions.manage_schedules') }}
                        </x-admin.btn>
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
                </div>
            </div>
        @endcan

        {{-- Lifecycle actions. Only the edges AdStatus permits from the ad's current
             status are rendered ($availableActions comes from the controller), and the
             whole panel needs `ads.approve` — the same permission the route enforces,
             so hiding a button is never the only thing standing in the way. --}}
        @can('ads.approve')
            @if (!empty($availableActions))
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.lifecycle') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            {{ __('admin.ads.transitions.current', [
                                'status' => __('admin.ads.statuses.' . $ad->status->value),
                            ]) }}
                        </p>

                        @error('action')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <form method="POST"
                              action="{{ route('admin.ads.transition', ['lang' => $lang, 'ad' => $ad->id]) }}">
                            @csrf

                            <div class="form-group">
                                <label for="transition_reason">{{ __('admin.ads.transitions.reason') }}</label>
                                <input type="text"
                                       id="transition_reason"
                                       name="reason"
                                       maxlength="500"
                                       value="{{ old('reason') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('reason')])>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('admin.ads.transitions.reason_help') }}</small>
                            </div>

                            <x-admin.group-btn>
                                @foreach ($availableActions as $action)
                                    <button type="submit"
                                            name="action"
                                            value="{{ $action }}"
                                            @class([
                                                'btn btn-sm',
                                                'btn-success' => in_array($action, ['approve', 'publish'], true),
                                                'btn-outline-danger' => in_array($action, ['reject', 'expire'], true),
                                                'btn-outline-secondary' => $action === 'unpublish',
                                            ])>
                                        {{ __('admin.ads.transitions.actions.' . $action) }}
                                    </button>
                                @endforeach
                            </x-admin.group-btn>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.identity') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.id') }}</th>
                                        <td>{{ $ad->id }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.title') }}</th>
                                        <td>{{ $adTitle }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.status') }}</th>
                                        <td>
                                            @include('admin.ads.partials.status-badge', ['status' => $ad->status])
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.media') }}</th>
                                        <td>
                                            <x-admin.badge variant="light">
                                                {{ \App\Support\Lang::t('admin.ads.file_types.' . $ad->file_type, ucfirst((string) $ad->file_type)) }}
                                            </x-admin.badge>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.description') }}</th>
                                        <td>{{ $adDescription ?? __('admin.ads.show.no_description') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.timing') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.start_date') }}</th>
                                        <td>{{ optional($ad->start_date)->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.table.end_date') }}</th>
                                        <td>{{ optional($ad->end_date)->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                    {{-- The window the device actually applies. Both
                                         bounds are computed in the controller via
                                         App\Support\AdValidity, so a date-only
                                         end_date shows the following midnight and the
                                         "plays through that day" rule is visible
                                         rather than implied. --}}
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.effective_window') }}</th>
                                        <td dir="ltr">
                                            {{ $validFrom?->format('Y-m-d H:i') ?? '—' }}
                                            &rarr;
                                            {{ $validBefore?->format('Y-m-d H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.form.duration_seconds') }}</th>
                                        <td>{{ $ad->duration_seconds }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.audit') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.form.owner') }}</th>
                                        <td>{{ $ad->creator?->name ?? '—' }}</td>
                                    </tr>
                                    {{-- Two actor domains, shown separately rather
                                         than conflated: the legacy `users` columns
                                         above, and the admin who actually performed
                                         the action below. Historical rows have no
                                         admin recorded, which is why these read "—"
                                         instead of guessing. --}}
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.created_by_admin') }}</th>
                                        <td>{{ $ad->creatorAdmin?->email ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.form.approved_by') }}</th>
                                        <td>{{ $ad->approver?->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.approved_by_admin') }}</th>
                                        <td>{{ $ad->approverAdmin?->email ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.approved_at') }}</th>
                                        <td>{{ optional($ad->approved_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.created_at') }}</th>
                                        <td>{{ optional($ad->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.ads.show.updated_at') }}</th>
                                        <td>{{ optional($ad->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.media') }}</h2>
                    </div>
                    <div class="card-body">
                        @include('admin.ads.partials.creative-preview', ['ad' => $ad, 'caption' => null])

                        @if ($ad->file_path)
                            <p class="text-muted small mb-0 mt-3">
                                {{ __('admin.ads.show.file_path') }}:
                                <code class="admin-wrap-anywhere">{{ $ad->file_path }}</code>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.linked_screens') }}</h2>
                        <x-admin.badge variant="light">{{ $ad->screens->count() }}</x-admin.badge>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.ads.table.screens') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.place') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.play_order') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ad->screens as $screen)
                                        <tr>
                                            <td><code>{{ $screen->code }}</code></td>
                                            <td>{{ $placeName($screen) ?? '—' }}</td>
                                            <td>{{ $screen->pivot->play_order }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.ads.show.linked_screens_empty'),
                                            'icon' => 'monitor',
                                        ])
                                    @endforelse
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
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.upcoming_schedules') }}</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.ads.table.screens') }}</th>
                                        <th scope="col">{{ __('admin.schedules.form.start_time') }}</th>
                                        <th scope="col">{{ __('admin.schedules.form.end_time') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($upcomingSchedules as $schedule)
                                        <tr>
                                            <td>{{ $screenLabel($schedule->screen) }}</td>
                                            <td dir="ltr">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td dir="ltr">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.ads.show.upcoming_schedules_empty'),
                                            'icon' => 'calendar',
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
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.past_schedules') }}</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.ads.table.screens') }}</th>
                                        <th scope="col">{{ __('admin.schedules.form.start_time') }}</th>
                                        <th scope="col">{{ __('admin.schedules.form.end_time') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- take(5) preserved verbatim from the pre-migration view. --}}
                                    @forelse ($pastSchedules->take(5) as $schedule)
                                        <tr>
                                            <td>{{ $screenLabel($schedule->screen) }}</td>
                                            <td dir="ltr">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td dir="ltr">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.ads.show.past_schedules_empty'),
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

        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.playback_summary') }}</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.ads.show.date') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.plays') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.total_duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($playbackStats as $date => $stat)
                                        <tr>
                                            <td dir="ltr">{{ $date }}</td>
                                            <td>{{ $stat['plays'] }}</td>
                                            <td>{{ $stat['duration'] }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.ads.show.playback_summary_empty'),
                                            'icon' => 'bar-chart-2',
                                        ])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.ads.show.recent_playbacks') }}</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.ads.table.screens') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.played_at') }}</th>
                                        <th scope="col">{{ __('admin.ads.show.duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ad->playbacks as $log)
                                        <tr>
                                            <td>{{ $log->screen?->code ?? '—' }}</td>
                                            <td dir="ltr">{{ optional($log->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td>{{ $log->duration }}</td>
                                        </tr>
                                    @empty
                                        @include('admin.partials.empty-state', [
                                            'colspan' => 3,
                                            'message' => __('admin.ads.show.recent_playbacks_empty'),
                                            'icon' => 'play-circle',
                                        ])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
