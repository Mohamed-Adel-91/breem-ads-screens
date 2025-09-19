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
                        <div class="table-container">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <form method="GET" class="row g-2 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Screen status') }}</label>
                                            <select name="screen_status" class="form-control">
                                                <option value="">-- {{ __('All statuses') }} --</option>
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected(($filters['screen_status'] ?? '') === $value)>{{ ucfirst($label) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Screen') }}</label>
                                            <select name="screen_id" class="form-control">
                                                <option value="">-- {{ __('All screens') }} --</option>
                                                @foreach ($screens as $screen)
                                                    <option value="{{ $screen->id }}" @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                                        {{ $screen->code }} @if ($screen->place)
                                                            — {{ data_get($screen->place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place->id]) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('From date') }}</label>
                                            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('To date') }}</label>
                                            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Ad') }}</label>
                                            <select name="ad_id" class="form-control">
                                                <option value="">-- {{ __('All ads') }} --</option>
                                                @foreach ($ads as $ad)
                                                    <option value="{{ $ad->id }}" @selected(($filters['ad_id'] ?? '') == $ad->id)>{{ data_get($ad->getTranslations('title'), app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Played from') }}</label>
                                            <input type="date" name="played_from" value="{{ $filters['played_from'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Played to') }}</label>
                                            <input type="date" name="played_to" value="{{ $filters['played_to'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                            <a href="{{ route('admin.logs.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @php
                                $queryParams = collect($filters)->filter(fn ($value) => filled($value))->all();
                            @endphp

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a href="{{ route('admin.logs.download', array_merge(['lang' => $lang, 'type' => 'system'])) }}" class="btn btn-outline-secondary btn-sm">{{ __('Download system log') }}</a>
                                <a href="{{ route('admin.logs.download', array_merge(['lang' => $lang, 'type' => 'screen'], $queryParams)) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export screen logs') }}</a>
                                <a href="{{ route('admin.logs.download', array_merge(['lang' => $lang, 'type' => 'playback'], $queryParams)) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export playback logs') }}</a>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ __('Screen status logs') }}</h5>
                                    <span class="badge bg-light text-dark">{{ $screenLogs->total() }} {{ __('entries') }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Screen') }}</th>
                                                    <th>{{ __('Place') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Reported at') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($screenLogs as $log)
                                                    <tr>
                                                        <td>{{ $log->screen?->code ?? '—' }}</td>
                                                        <td>{{ $log->screen?->place ? data_get($log->screen->place->getTranslations('name'), app()->getLocale()) : '—' }}</td>
                                                        <td>{{ ucfirst($log->status->value ?? '-') }}</td>
                                                        <td>{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">{{ __('No screen logs for the selected filters.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @include('admin.partials.pagination', ['data' => $screenLogs])
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ __('Playback logs') }}</h5>
                                    <span class="badge bg-light text-dark">{{ $playbackLogs->total() }} {{ __('entries') }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Screen') }}</th>
                                                    <th>{{ __('Ad') }}</th>
                                                    <th>{{ __('Played at') }}</th>
                                                    <th>{{ __('Duration (seconds)') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($playbackLogs as $log)
                                                    <tr>
                                                        <td>{{ $log->screen?->code ?? '—' }}</td>
                                                        <td>{{ $log->ad ? data_get($log->ad->getTranslations('title'), app()->getLocale()) : '—' }}</td>
                                                        <td>{{ optional($log->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                                        <td>{{ $log->duration }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">{{ __('No playback logs for the selected filters.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @include('admin.partials.pagination', ['data' => $playbackLogs])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
