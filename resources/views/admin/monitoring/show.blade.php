@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <div class="row gutters">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">{{ __('Screen monitoring') }} — {{ $screen->code }}</h5>
                                    <small class="text-muted">{{ data_get($screen->place?->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place_id]) }}</small>
                                </div>
                                <a href="{{ route('admin.monitoring.index', ['lang' => $lang]) }}" class="btn btn-light btn-sm">{{ __('Back to monitoring') }}</a>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="info-stats3">
                                            <span>{{ __('Status') }}</span>
                                            <h6>{{ ucfirst($screen->status->value ?? '-') }}</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-stats3">
                                            <span>{{ __('Last heartbeat') }}</span>
                                            <h6>{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-stats3">
                                            <span>{{ __('Uptime (7 days)') }}</span>
                                            <h6>{{ $uptime !== null ? $uptime . '%' : '—' }}</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-stats3">
                                            <span>{{ __('Active schedules') }}</span>
                                            <h6>{{ $screen->schedules->where('is_active', true)->count() }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Device UID') }}</h6>
                                        <p class="mb-0">{{ $screen->device_uid ?? '—' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Total schedules') }}</h6>
                                        <p class="mb-0">{{ $screen->schedules->count() }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Attached ads') }}</h6>
                                        <p class="mb-0">{{ $screen->ads->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @can('monitoring.manage')
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">{{ __('Acknowledge alert') }}</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('admin.monitoring.screens.acknowledge', ['lang' => $lang, 'screen' => $screen->id]) }}" class="row g-3 align-items-end">
                                        @csrf
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Update status to') }}</label>
                                            <select name="status" class="form-control">
                                                <option value="online" @selected(old('status', 'online') === 'online')>{{ __('Online') }}</option>
                                                <option value="maintenance" @selected(old('status') === 'maintenance')>{{ __('Maintenance') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Note (optional)') }}</label>
                                            <textarea name="note" class="form-control" rows="1" placeholder="{{ __('Add details about the intervention') }}">{{ old('note') }}</textarea>
                                        </div>
                                        <div class="col-md-2 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary w-100">{{ __('Confirm') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endcan

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Upcoming schedules') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Ad') }}</th>
                                                <th>{{ __('Start time') }}</th>
                                                <th>{{ __('End time') }}</th>
                                                <th>{{ __('Active') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($screen->schedules as $schedule)
                                                <tr>
                                                    <td>{{ optional($schedule->ad)->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $schedule->ad_id]) }}</td>
                                                    <td>{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    <td>{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    <td>
                                                        @if ($schedule->is_active)
                                                            <span class="badge bg-success">{{ __('Yes') }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ __('No') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">{{ __('No schedules configured for this screen.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Attached ads') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Play order') }}</th>
                                                <th>{{ __('Ad title') }}</th>
                                                <th>{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($screen->ads as $ad)
                                                <tr>
                                                    <td>{{ $ad->pivot->play_order }}</td>
                                                    <td>{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</td>
                                                    <td>{{ ucfirst($ad->status->value ?? '-') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">{{ __('No ads are attached to this screen.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Recent monitoring logs') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Reported at') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentLogs as $log)
                                                <tr>
                                                    <td>{{ ucfirst($log->status->value ?? '-') }}</td>
                                                    <td>{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted">{{ __('No monitoring logs recorded yet.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @include('admin.partials.pagination', ['data' => $recentLogs])
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Recent playbacks') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Ad') }}</th>
                                                <th>{{ __('Played at') }}</th>
                                                <th>{{ __('Duration (seconds)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentPlaybacks as $playback)
                                                <tr>
                                                    <td>{{ optional($playback->ad)->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $playback->ad_id]) }}</td>
                                                    <td>{{ optional($playback->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    <td>{{ $playback->duration }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">{{ __('No playback activity recorded yet.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @include('admin.partials.pagination', ['data' => $recentPlaybacks])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
