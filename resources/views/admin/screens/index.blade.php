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
                                <h5 class="mb-0">{{ __('Screens') }}</h5>
                                <a href="{{ route('admin.screens.create', ['lang' => $lang]) }}" class="btn btn-success btn-sm">{{ __('Create screen') }}</a>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Search') }}</label>
                                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search code or device UID') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="">-- {{ __('All') }} --</option>
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
                                                    {{ $place->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                        <a href="{{ route('admin.screens.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                    </div>
                                </form>

                                <div class="row mb-3">
                                    <div class="col-md-3"><div class="info-stats3"><span>{{ __('Total') }}</span><h6>{{ $stats['total'] }}</h6></div></div>
                                    <div class="col-md-3"><div class="info-stats3"><span>{{ __('Online') }}</span><h6>{{ $stats['online'] ?? 0 }}</h6></div></div>
                                    <div class="col-md-3"><div class="info-stats3"><span>{{ __('Offline') }}</span><h6>{{ $stats['offline'] ?? 0 }}</h6></div></div>
                                    <div class="col-md-3"><div class="info-stats3"><span>{{ __('Maintenance') }}</span><h6>{{ $stats['maintenance'] ?? 0 }}</h6></div></div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table custom-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ __('Code') }}</th>
                                                <th>{{ __('Place') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Active schedules') }}</th>
                                                <th>{{ __('Last heartbeat') }}</th>
                                                <th>{{ __('admin.table.options') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($screens as $screen)
                                                <tr>
                                                    <td>{{ $loop->iteration + ($screens->currentPage() - 1) * $screens->perPage() }}</td>
                                                    <td>{{ $screen->code }}</td>
                                                    <td>{{ $screen->place?->getTranslation('name', app()->getLocale()) ?? '—' }}</td>
                                                    <td><span class="badge bg-info text-dark">{{ ucfirst($screen->status->value) }}</span></td>
                                                    <td>{{ $screen->active_schedule_count }}</td>
                                                    <td>{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="{{ route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                            <a href="{{ route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">{{ __('No screens match the selected filters.') }}</td>
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
    </div>
@endsection
