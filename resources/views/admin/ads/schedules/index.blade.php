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
                                <h5 class="mb-0">{{ __('Manage schedules for') }} {{ $ad->getTranslation('title', app()->getLocale()) ?? '#' . $ad->id }}</h5>
                                <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-light btn-sm">{{ __('Back to ad') }}</a>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-2 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Screen') }}</label>
                                        <select name="screen_id" class="form-control">
                                            <option value="">-- {{ __('All') }} --</option>
                                            @foreach ($availableScreens as $screen)
                                                <option value="{{ $screen->id }}" @selected(($filters['screen_id'] ?? '') == $screen->id)>{{ $screen->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Active') }}</label>
                                        <select name="is_active" class="form-control">
                                            <option value="">-- {{ __('All') }} --</option>
                                            <option value="1" @selected(($filters['is_active'] ?? '') === '1')>{{ __('Yes') }}</option>
                                            <option value="0" @selected(($filters['is_active'] ?? '') === '0')>{{ __('No') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('From date') }}</label>
                                        <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('To date') }}</label>
                                        <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                        <a href="{{ route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                    </div>
                                </form>

                                <div class="row mb-3">
                                    <div class="col-md-4"><div class="info-stats3"><span>{{ __('Total schedules') }}</span><h6>{{ $stats['total'] }}</h6></div></div>
                                    <div class="col-md-4"><div class="info-stats3"><span>{{ __('Active') }}</span><h6>{{ $stats['active'] }}</h6></div></div>
                                    <div class="col-md-4"><div class="info-stats3"><span>{{ __('Inactive') }}</span><h6>{{ $stats['inactive'] }}</h6></div></div>
                                </div>

                                <h6 class="mb-3">{{ __('Create new schedule') }}</h6>
                                <form method="POST" action="{{ route('admin.ads.schedules.store', ['lang' => $lang, 'ad' => $ad->id]) }}" class="row g-3 align-items-end mb-4">
                                    @csrf
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Screen') }}</label>
                                        <select name="screen_id" class="form-control">
                                            @foreach ($availableScreens as $screen)
                                                <option value="{{ $screen->id }}">{{ $screen->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Start time') }}</label>
                                        <input type="datetime-local" name="start_time" class="form-control" value="{{ old('start_time') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('End time') }}</label>
                                        <input type="datetime-local" name="end_time" class="form-control" value="{{ old('end_time') }}">
                                    </div>
                                    <div class="col-md-2 form-check mt-4">
                                        <input type="checkbox" class="form-check-input" name="is_active" id="create-is-active" value="1" checked>
                                        <label for="create-is-active" class="form-check-label">{{ __('Active') }}</label>
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-success">{{ __('Add schedule') }}</button>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Screen') }}</th>
                                                <th>{{ __('Start time') }}</th>
                                                <th>{{ __('End time') }}</th>
                                                <th>{{ __('Active') }}</th>
                                                <th>{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($schedules as $schedule)
                                                <tr>
                                                    <td>{{ $schedule->screen?->code ?? __('Screen removed') }}</td>
                                                    <td>{{ $schedule->start_time->format('Y-m-d H:i') }}</td>
                                                    <td>{{ $schedule->end_time->format('Y-m-d H:i') }}</td>
                                                    <td>
                                                        @if ($schedule->is_active)
                                                            <span class="badge bg-success">{{ __('Yes') }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ __('No') }}</span>
                                                        @endif
                                                    </td>
                                                    <td><a class="btn btn-sm btn-outline-primary" href="#schedule-{{ $schedule->id }}">{{ __('Edit') }}</a></td>
                                                </tr>
                                                <tr id="schedule-{{ $schedule->id }}">
                                                    <td colspan="5">
                                                        <form method="POST" action="{{ route('admin.ads.schedules.update', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id]) }}" class="row g-2 align-items-end">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="col-md-3">
                                                                <label class="form-label">{{ __('Screen') }}</label>
                                                                <select name="screen_id" class="form-control form-control-sm">
                                                                    @foreach ($availableScreens as $screen)
                                                                        <option value="{{ $screen->id }}" @selected($screen->id == $schedule->screen_id)>{{ $screen->code }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">{{ __('Start time') }}</label>
                                                                <input type="datetime-local" name="start_time" class="form-control form-control-sm" value="{{ $schedule->start_time->format('Y-m-d\\TH:i') }}">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">{{ __('End time') }}</label>
                                                                <input type="datetime-local" name="end_time" class="form-control form-control-sm" value="{{ $schedule->end_time->format('Y-m-d\\TH:i') }}">
                                                            </div>
                                                            <div class="col-md-3 form-check mt-4">
                                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="schedule-active-{{ $schedule->id }}" @checked($schedule->is_active)>
                                                                <label for="schedule-active-{{ $schedule->id }}" class="form-check-label">{{ __('Active') }}</label>
                                                            </div>
                                                            <div class="col-md-12 d-flex justify-content-end">
                                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Save changes') }}</button>
                                                            </div>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.ads.schedules.destroy', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id]) }}" class="d-flex justify-content-end mt-2" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">{{ __('No schedules found.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @include('admin.partials.pagination', ['data' => $schedules])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
