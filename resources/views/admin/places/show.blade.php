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
                                <h5 class="mb-0">{{ data_get($place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}</h5>
                                <div class="d-flex gap-2">
                                    @can('places.view')
                                        <a href="{{ route('admin.places.index', ['lang' => $lang]) }}" class="btn btn-sm btn-light">{{ __('Back to list') }}</a>
                                    @endcan
                                    @can('places.edit')
                                        <a href="{{ route('admin.places.edit', ['lang' => $lang, 'place' => $place->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('places.delete')
                                        <form action="{{ route('admin.places.destroy', ['lang' => $lang, 'place' => $place->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this place?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Type') }}</h6>
                                        <p class="mb-0">{{ ucfirst($place->type?->value ?? '-') }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Address') }}</h6>
                                        <p class="mb-0">{{ data_get($place->getTranslations('address'), app()->getLocale()) ?? '—' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted">{{ __('Screens installed') }}</h6>
                                        <p class="mb-0">{{ $place->screens->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ __('Screens at this place') }}</h5>
                                @can('screens.create')
                                    <a href="{{ route('admin.screens.create', ['lang' => $lang]) }}" class="btn btn-sm btn-success">{{ __('Add screen') }}</a>
                                @endcan
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Code') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Device UID') }}</th>
                                                <th>{{ __('Last heartbeat') }}</th>
                                                <th>{{ __('Schedules') }}</th>
                                                <th>{{ __('Ads attached') }}</th>
                                                <th>{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($place->screens as $screen)
                                                <tr>
                                                    <td>{{ $screen->code }}</td>
                                                    <td>
                                                        <span class="badge bg-info text-dark">{{ ucfirst($screen->status->value ?? '-') }}</span>
                                                    </td>
                                                    <td>{{ $screen->device_uid ?? '—' }}</td>
                                                    <td>{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</td>
                                                    <td>{{ $screen->schedules_count }}</td>
                                                    <td>{{ $screen->ads_count }}</td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            @can('screens.view')
                                                                <a href="{{ route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                                            @endcan
                                                            @can('screens.edit')
                                                                <a href="{{ route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">{{ __('No screens are registered for this place yet.') }}</td>
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
