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
                                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('Status') }}</label>
                                            <select name="status" class="form-control">
                                                <option value="">-- {{ __('All') }} --</option>
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                                        {{ ucfirst(__($label)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Screen') }}</label>
                                            <select name="screen_id" class="form-control">
                                                <option value="">-- {{ __('All screens') }} --</option>
                                                @foreach ($screens as $screen)
                                                    <option value="{{ $screen->id }}" @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                                        {{ $screen->code }} @if($screen->place) - {{ $screen->place->getTranslation('name', app()->getLocale()) }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('From date') }}</label>
                                            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('To date') }}</label>
                                            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                            <a href="{{ route('admin.ads.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                            <a href="{{ route('admin.ads.create', ['lang' => $lang]) }}" class="btn btn-success ms-2">{{ __('admin.buttons.new') }}</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('Total ads') }}</span>
                                        <h6>{{ $stats['total'] }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('Active') }}</span>
                                        <h6>{{ $stats['active'] }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('Pending') }}</span>
                                        <h6>{{ $stats['pending'] }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('Expired') }}</span>
                                        <h6>{{ $stats['expired'] }}</h6>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Title') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Screens') }}</th>
                                            <th>{{ __('Schedules') }}</th>
                                            <th>{{ __('Start date') }}</th>
                                            <th>{{ __('End date') }}</th>
                                            <th>{{ __('admin.table.options') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ads as $ad)
                                            <tr>
                                                <td>{{ $loop->iteration + ($ads->currentPage() - 1) * $ads->perPage() }}</td>
                                                <td>{{ $ad->getTranslation('title', app()->getLocale()) ?? __('(No title)') }}</td>
                                                <td><span class="badge bg-info text-dark">{{ ucfirst($ad->status->value) }}</span></td>
                                                <td>{{ $ad->screens->count() }}</td>
                                                <td>{{ $ad->schedules->count() }}</td>
                                                <td>{{ optional($ad->start_date)->format('Y-m-d') ?? '—' }}</td>
                                                <td>{{ optional($ad->end_date)->format('Y-m-d') ?? '—' }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                        <a href="{{ route('admin.ads.edit', ['lang' => $lang, 'ad' => $ad->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                                        <form action="{{ route('admin.ads.destroy', ['lang' => $lang, 'ad' => $ad->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">{{ __('No ads found for the current filters.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination', ['data' => $ads])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
