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
                                <h5 class="mb-0">{{ $screen->code }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-primary btn-sm">{{ __('Edit screen') }}</a>
                                    <a href="{{ route('admin.screens.index', ['lang' => $lang]) }}" class="btn btn-light btn-sm">{{ __('Back to list') }}</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <strong>{{ __('Place') }}</strong>
                                        <div>{{ $screen->place?->getTranslation('name', app()->getLocale()) ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Status') }}</strong>
                                        <div>{{ ucfirst($screen->status->value) }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Last heartbeat') }}</strong>
                                        <div>{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Device UID') }}</strong>
                                        <div>{{ $screen->device_uid ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Active schedules') }}</strong>
                                        <div>{{ $screen->schedules->where('is_active', true)->count() }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Linked ads') }}</strong>
                                        <div>{{ $screen->ads->count() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Linked ads') }}</h6></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @forelse ($screen->ads as $ad)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</span>
                                            <span class="badge bg-light text-dark">{{ __('Order') }}: {{ $ad->pivot->play_order }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ __('No ads linked to this screen.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Uptime (last 7 days)') }}</h6></div>
                            <div class="card-body">
                                @if (!is_null($uptime))
                                    <div class="display-5">{{ $uptime }}%</div>
                                    <p class="text-muted mb-0">{{ __('Percentage of logs reporting the screen as online during the last 7 days.') }}</p>
                                @else
                                    <p class="text-muted mb-0">{{ __('Not enough data to calculate uptime.') }}</p>
                                @endif
                                <ul class="list-group list-group-flush mt-3">
                                    <li class="list-group-item d-flex justify-content-between"><span>{{ __('Online events') }}</span><span>{{ $logSummary['online'] ?? 0 }}</span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>{{ __('Offline events') }}</span><span>{{ $logSummary['offline'] ?? 0 }}</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Active schedules') }}</h6></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @forelse ($screen->schedules->sortBy('start_time') as $schedule)
                                        <li class="list-group-item">
                                            <div class="fw-semibold">{{ $schedule->ad?->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $schedule->ad_id]) }}</div>
                                            <div class="text-muted">{{ $schedule->start_time->format('Y-m-d H:i') }} → {{ $schedule->end_time->format('Y-m-d H:i') }}</div>
                                            <div class="small">{{ $schedule->is_active ? __('Active') : __('Inactive') }}</div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ __('No schedules configured.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Recent logs') }}</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Reported at') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentLogs as $log)
                                                <tr>
                                                    <td>{{ ucfirst($log->status->value) }}</td>
                                                    <td>{{ $log->reported_at->format('Y-m-d H:i') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted">{{ __('No logs recorded yet.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @include('admin.partials.pagination', ['data' => $recentLogs])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">{{ __('Recent playbacks') }}</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Ad') }}</th>
                                                <th>{{ __('Played at') }}</th>
                                                <th>{{ __('Duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentPlaybacks as $log)
                                                <tr>
                                                    <td>{{ $log->ad?->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $log->ad_id]) }}</td>
                                                    <td>{{ optional($log->played_at)->format('Y-m-d H:i') }}</td>
                                                    <td>{{ $log->duration }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">{{ __('No playback events recorded yet.') }}</td>
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
