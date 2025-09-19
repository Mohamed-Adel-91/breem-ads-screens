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
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Search') }}</label>
                                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search by name') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Type') }}</label>
                                            <select name="type" class="form-control">
                                                <option value="">-- {{ __('All types') }} --</option>
                                                @foreach ($types as $type)
                                                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                            <a href="{{ route('admin.reports.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">{{ __('Generate new report') }}</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('admin.reports.generate', ['lang' => $lang]) }}" class="row g-3">
                                        @csrf
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Report name') }}</label>
                                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="{{ __('Enter a descriptive name') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Report type') }}</label>
                                            <select name="type" class="form-control">
                                                @foreach ($types as $type)
                                                    <option value="{{ $type }}" @selected(old('type', $types[0] ?? '') === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Screen (optional)') }}</label>
                                            <select name="screen_id" class="form-control">
                                                <option value="">-- {{ __('All screens') }} --</option>
                                                @foreach ($screens as $screen)
                                                    <option value="{{ $screen->id }}" @selected(old('screen_id') == $screen->id)>{{ $screen->code }} @if ($screen->place)
                                                            — {{ data_get($screen->place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place->id]) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Ad (optional)') }}</label>
                                            <select name="ad_id" class="form-control">
                                                <option value="">-- {{ __('All ads') }} --</option>
                                                @foreach ($ads as $ad)
                                                    <option value="{{ $ad->id }}" @selected(old('ad_id') == $ad->id)>{{ data_get($ad->getTranslations('title'), app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('From date') }}</label>
                                            <input type="date" name="from_date" value="{{ old('from_date') }}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('To date') }}</label>
                                            <input type="date" name="to_date" value="{{ old('to_date') }}" class="form-control">
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-success">{{ __('Generate report') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ __('Existing reports') }}</h5>
                                    <span class="badge bg-light text-dark">{{ $reports->total() }} {{ __('entries') }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('Name') }}</th>
                                                    <th>{{ __('Type') }}</th>
                                                    <th>{{ __('Generated by') }}</th>
                                                    <th>{{ __('Created at') }}</th>
                                                    <th>{{ __('admin.table.options') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($reports as $report)
                                                    <tr>
                                                        <td>{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                                        <td>{{ $report->name }}</td>
                                                        <td>{{ ucfirst(str_replace('-', ' ', $report->type)) }}</td>
                                                        <td>{{ optional($report->generator)->name ?? __('System') }}</td>
                                                        <td>{{ optional($report->created_at)->format('Y-m-d H:i') }}</td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <a href="{{ route('admin.reports.show', ['lang' => $lang, 'report' => $report->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                                <a href="{{ route('admin.reports.download', ['lang' => $lang, 'report' => $report->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Download') }}</a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">{{ __('No reports generated yet.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @include('admin.partials.pagination', ['data' => $reports])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
