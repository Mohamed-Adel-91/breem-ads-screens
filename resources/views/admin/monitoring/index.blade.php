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
                                            <label class="form-label">{{ __('Search') }}</label>
                                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search by code or device UID') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Status') }}</label>
                                            <select name="status" class="form-control">
                                                <option value="">-- {{ __('All statuses') }} --</option>
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ ucfirst($label) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Place') }}</label>
                                            <select name="place_id" class="form-control">
                                                <option value="">-- {{ __('All places') }} --</option>
                                                @foreach ($places as $place)
                                                    <option value="{{ $place->id }}" @selected(($filters['place_id'] ?? '') == $place->id)>
                                                        {{ data_get($place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 form-check mt-4">
                                            <input type="checkbox" class="form-check-input" name="has_alerts" value="1" id="filter-has-alerts" @checked(($filters['has_alerts'] ?? false))>
                                            <label class="form-check-label" for="filter-has-alerts">{{ __('Show screens with alerts only') }}</label>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                            <a href="{{ route('admin.monitoring.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row mb-3">
                                @foreach ($summary as $status => $count)
                                    <div class="col-md-3">
                                        <div class="info-stats3">
                                            <span>{{ ucfirst($status) }}</span>
                                            <h6>{{ $count }}</h6>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Code') }}</th>
                                            <th>{{ __('Place') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Last report') }}</th>
                                            <th>{{ __('Offline logs (24h)') }}</th>
                                            <th>{{ __('Active schedules') }}</th>
                                            <th>{{ __('admin.table.options') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($screens as $screen)
                                            @php $latestLog = $screen->logs->first(); @endphp
                                            <tr class="align-middle">
                                                <td>{{ $loop->iteration + ($screens->currentPage() - 1) * $screens->perPage() }}</td>
                                                <td>{{ $screen->code }}</td>
                                                <td>
                                                    @if ($screen->place)
                                                        {{ data_get($screen->place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place->id]) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-info text-dark">{{ ucfirst($screen->status->value ?? '-') }}</span></td>
                                                <td>
                                                    @if ($latestLog)
                                                        <div class="small text-muted">{{ $latestLog->status->value ?? '-' }}</div>
                                                        <div>{{ optional($latestLog->reported_at)->format('Y-m-d H:i') }}</div>
                                                    @else
                                                        <span class="text-muted">{{ __('No logs') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $screen->offline_logs_count }}</td>
                                                <td>{{ $screen->active_schedule_count }}</td>
                                                <td>
                                                    <a href="{{ route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">{{ __('No screens found for the selected filters.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination', ['data' => $screens])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
