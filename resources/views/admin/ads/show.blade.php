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
                                <h5 class="mb-0">{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad details') }}</h5>
                                <div class="d-flex gap-2">
                                    @can('ads.edit')
                                        <a href="{{ route('admin.ads.edit', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('ads.view')
                                        <a href="{{ route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-light btn-sm">{{ __('Manage schedules') }}</a>
                                    @endcan
                                    @can('ads.view')
                                        <a href="{{ route('admin.ads.index', ['lang' => $lang]) }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to list') }}</a>
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <strong>{{ __('Status') }}</strong>
                                        <div>{{ ucfirst($ad->status->value) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>{{ __('Start date') }}</strong>
                                        <div>{{ optional($ad->start_date)->format('Y-m-d') ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>{{ __('End date') }}</strong>
                                        <div>{{ optional($ad->end_date)->format('Y-m-d') ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>{{ __('Duration (seconds)') }}</strong>
                                        <div>{{ $ad->duration_seconds }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Owner') }}</strong>
                                        <div>{{ $ad->creator?->name ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Approved by') }}</strong>
                                        <div>{{ $ad->approver?->name ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ __('Creative') }}</strong>
                                        @if ($ad->file_url)
                                            <div><a href="{{ $ad->file_url }}" target="_blank">{{ __('Open asset') }}</a></div>
                                        @else
                                            <div>—</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Linked screens') }}</h6></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @forelse ($ad->screens as $screen)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $screen->code }} @if($screen->place) - {{ $screen->place->getTranslation('name', app()->getLocale()) }} @endif</span>
                                            <span class="badge bg-light text-dark">{{ __('Order') }}: {{ $screen->pivot->play_order }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ __('No screens linked yet.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Playback summary (last 7 days)') }}</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Plays') }}</th>
                                                <th>{{ __('Total duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($playbackStats as $date => $stat)
                                                <tr>
                                                    <td>{{ $date }}</td>
                                                    <td>{{ $stat['plays'] }}</td>
                                                    <td>{{ $stat['duration'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">{{ __('No playback records in the selected period.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gutters">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Upcoming schedules') }}</h6></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @forelse ($upcomingSchedules as $schedule)
                                        <li class="list-group-item">
                                            <div class="fw-semibold">{{ $schedule->screen?->code ?? __('Screen removed') }}</div>
                                            <div class="text-muted">{{ $schedule->start_time->format('Y-m-d H:i') }} → {{ $schedule->end_time->format('Y-m-d H:i') }}</div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ __('No upcoming schedules.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">{{ __('Past schedules') }}</h6></div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @forelse ($pastSchedules->take(5) as $schedule)
                                        <li class="list-group-item">
                                            <div class="fw-semibold">{{ $schedule->screen?->code ?? __('Screen removed') }}</div>
                                            <div class="text-muted">{{ $schedule->start_time->format('Y-m-d H:i') }} → {{ $schedule->end_time->format('Y-m-d H:i') }}</div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ __('No past schedules.') }}</li>
                                    @endforelse
                                </ul>
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
                                                <th>{{ __('Screen') }}</th>
                                                <th>{{ __('Played at') }}</th>
                                                <th>{{ __('Duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($ad->playbacks as $log)
                                                <tr>
                                                    <td>{{ $log->screen?->code ?? '—' }}</td>
                                                    <td>{{ optional($log->played_at)->format('Y-m-d H:i') }}</td>
                                                    <td>{{ $log->duration }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">{{ __('No playback logs available.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
