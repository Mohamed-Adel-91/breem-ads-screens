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
                                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search by name or address') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Type') }}</label>
                                            <select name="type" class="form-control">
                                                <option value="">-- {{ __('All types') }} --</option>
                                                @foreach ($types as $value => $label)
                                                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ ucfirst(__($label)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary me-2">{{ __('admin.buttons.filter') }}</button>
                                            <a href="{{ route('admin.places.index', ['lang' => $lang]) }}" class="btn btn-light">{{ __('admin.buttons.reset') }}</a>
                                            @can('places.create')
                                                <a href="{{ route('admin.places.create', ['lang' => $lang]) }}" class="btn btn-success ms-2">{{ __('admin.buttons.new') }}</a>
                                            @endcan
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('Total places') }}</span>
                                        <h6>{{ $stats['total'] }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-stats3">
                                        <span>{{ __('With screens') }}</span>
                                        <h6>{{ $stats['with_screens'] }}</h6>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Screens') }}</th>
                                            <th>{{ __('Address') }}</th>
                                            <th>{{ __('admin.table.options') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($places as $place)
                                            <tr>
                                                <td>{{ $loop->iteration + ($places->currentPage() - 1) * $places->perPage() }}</td>
                                                <td>{{ data_get($place->getTranslations('name'), app()->getLocale()) ?? __('(No name)') }}</td>
                                                <td>{{ ucfirst(__($types[$place->type?->value] ?? $place->type?->value ?? '-')) }}</td>
                                                <td>{{ $place->screens_count }}</td>
                                                <td>{{ data_get($place->getTranslations('address'), app()->getLocale()) ?? '—' }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        @can('places.view')
                                                            <a href="{{ route('admin.places.show', ['lang' => $lang, 'place' => $place->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                        @endcan
                                                        @can('places.edit')
                                                            <a href="{{ route('admin.places.edit', ['lang' => $lang, 'place' => $place->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                                        @endcan
                                                        @can('places.delete')
                                                            <form action="{{ route('admin.places.destroy', ['lang' => $lang, 'place' => $place->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">{{ __('No places match the current filters.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination', ['data' => $places])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
