@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $adsIndexUrl = route('admin.ads.index', ['lang' => $lang]);
        $adShowUrl = route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]);
        $indexUrl = route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id]);

        $adTitle = data_get($ad->getTranslations('title'), app()->getLocale())
            ?: __('admin.ads.untitled', ['id' => $ad->id]);

        $screenLabel = function ($screen) {
            if (!$screen) {
                return null;
            }

            $label = $screen->code;
            $placeName = $screen->place
                ? data_get($screen->place->getTranslations('name'), app()->getLocale())
                : null;

            if ($placeName) {
                $label .= ' — ' . $placeName;
            }

            return $label;
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => __('admin.schedules.for_ad', ['title' => $adTitle]),
            'subtitle' => __('admin.schedules.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads'), 'url' => $adsIndexUrl],
                ['label' => $adTitle, 'url' => $adShowUrl],
                ['label' => __('admin.sidebar.ads_system_schedules')],
            ],
            'secondaryAction' => [
                'href' => $adShowUrl,
                'label' => __('admin.schedules.actions.back_to_ad'),
                'icon' => 'arrow-left',
            ],
        ])

        {{-- Filter card: query parameter names (`screen_id`, `is_active`,
             `from_date`, `to_date`) are preserved verbatim. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.schedules.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="screen_id">{{ __('admin.schedules.filters.screen') }}</label>
                                <select id="screen_id" name="screen_id" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all_screens') }}</option>
                                    @foreach ($availableScreens as $screen)
                                        <option value="{{ $screen->id }}"
                                            @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                            {{ $screenLabel($screen) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="is_active">{{ __('admin.schedules.filters.is_active') }}</label>
                                <select id="is_active" name="is_active" class="form-control">
                                    <option value="">{{ __('admin.schedules.filters.all') }}</option>
                                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>
                                        {{ __('admin.schedules.filters.yes') }}
                                    </option>
                                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>
                                        {{ __('admin.schedules.filters.no') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="from_date">{{ __('admin.schedules.filters.from_date') }}</label>
                                <input type="date"
                                       id="from_date"
                                       name="from_date"
                                       class="form-control"
                                       value="{{ $filters['from_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label for="to_date">{{ __('admin.schedules.filters.to_date') }}</label>
                                <input type="date"
                                       id="to_date"
                                       name="to_date"
                                       class="form-control"
                                       value="{{ $filters['to_date'] ?? '' }}">
                            </div>
                        </div>

                        <div class="col-12">
                            <x-admin.group-btn class="justify-content-end">
                                <x-admin.btn type="submit" variant="primary" icon="filter">
                                    {{ __('admin.buttons.filter') }}
                                </x-admin.btn>
                                <x-admin.btn :href="$indexUrl" variant="light" icon="rotate-ccw">
                                    {{ __('admin.buttons.reset') }}
                                </x-admin.btn>
                            </x-admin.group-btn>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.stats.total') }}</span>
                        <span class="admin-stat-value">{{ localized_digits($stats['total'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.stats.active') }}</span>
                        <span class="admin-stat-value text-success">{{ localized_digits($stats['active'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card admin-stat-card mb-4">
                    <div class="card-body">
                        <span class="admin-stat-label">{{ __('admin.schedules.stats.inactive') }}</span>
                        <span class="admin-stat-value text-warning">{{ localized_digits($stats['inactive'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @can('ads.schedule')
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.schedules.create.heading') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.ads.schedules.store', ['lang' => $lang, 'ad' => $ad->id]) }}">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="create_screen_id">
                                        {{ __('admin.schedules.form.screen') }}
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <select id="create_screen_id"
                                            name="screen_id"
                                            required
                                            @class(['form-control', 'is-invalid' => $errors->has('screen_id')])>
                                        @foreach ($availableScreens as $screen)
                                            <option value="{{ $screen->id }}" @selected(old('screen_id') == $screen->id)>
                                                {{ $screenLabel($screen) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('screen_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="create_start_time">
                                        {{ __('admin.schedules.form.start_time') }}
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="datetime-local"
                                           id="create_start_time"
                                           name="start_time"
                                           dir="ltr"
                                           required
                                           value="{{ old('start_time') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('start_time')])>
                                    @error('start_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="create_end_time">
                                        {{ __('admin.schedules.form.end_time') }}
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="datetime-local"
                                           id="create_end_time"
                                           name="end_time"
                                           dir="ltr"
                                           required
                                           value="{{ old('end_time') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('end_time')])>
                                    @error('end_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    {{-- Standard checkbox boolean submission: the hidden field
                                         guarantees `is_active=0` reaches the controller when the
                                         box is unchecked. Without it the browser sends nothing
                                         and StoreScheduleRequest's `?? true` fallback made an
                                         inactive schedule impossible to create. --}}
                                    <input type="hidden" name="is_active" value="0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="create_is_active"
                                               name="is_active"
                                               value="1"
                                               @checked(old('is_active', true))>
                                        <label class="custom-control-label" for="create_is_active">
                                            {{ __('admin.schedules.form.is_active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <p class="text-muted small">{{ __('admin.schedules.form.conflict_notice') }}</p>
                                <x-admin.group-btn class="justify-content-end">
                                    <x-admin.btn type="submit" variant="success" icon="plus">
                                        {{ __('admin.schedules.create.submit') }}
                                    </x-admin.btn>
                                </x-admin.group-btn>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        @include('admin.partials.results-summary', [
            'data' => $schedules,
            'label' => __('admin.schedules.results_label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.schedules.table.screen') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.start_time') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.end_time') }}</th>
                                <th scope="col">{{ __('admin.schedules.table.is_active') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules as $schedule)
                                @php($editPanelId = 'schedule-edit-' . $schedule->id)
                                <tr>
                                    <td>
                                        <span class="d-block font-weight-bold">
                                            {{ $schedule->screen?->code ?? __('admin.ads.show.screen_removed') }}
                                        </span>
                                        @if ($schedule->screen?->place)
                                            <small class="text-muted">
                                                {{ data_get($schedule->screen->place->getTranslations('name'), app()->getLocale()) ?: '—' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td dir="ltr">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td dir="ltr">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <x-admin.badge :variant="$schedule->is_active ? 'success' : 'secondary'">
                                            {{ $schedule->is_active
                                                ? __('admin.schedules.filters.yes')
                                                : __('admin.schedules.filters.no') }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        @can('ads.schedule')
                                            <x-admin.group-btn>
                                                {{-- Bootstrap 4 collapse: no Alpine, no extra JS file. --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-toggle="collapse"
                                                        data-target="#{{ $editPanelId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $editPanelId }}">
                                                    <i class="fe fe-edit-2" aria-hidden="true"></i>
                                                    <span>{{ __('admin.schedules.actions.edit') }}</span>
                                                </button>
                                                <x-admin.btn
                                                    :href="route('admin.ads.schedules.destroy', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id])"
                                                    method="DELETE"
                                                    variant="outline-danger"
                                                    size="sm"
                                                    icon="trash-2"
                                                    :confirm="__('admin.schedules.actions.delete_confirm')">
                                                    {{ __('admin.schedules.actions.delete') }}
                                                </x-admin.btn>
                                            </x-admin.group-btn>
                                        @endcan
                                    </td>
                                </tr>

                                @can('ads.schedule')
                                    <tr class="admin-inline-editor-row">
                                        <td colspan="5" class="p-0 border-0">
                                            <div class="collapse" id="{{ $editPanelId }}">
                                                <div class="admin-inline-editor">
                                                    <h3 class="admin-section-title">
                                                        {{ __('admin.schedules.edit.heading', ['id' => $schedule->id]) }}
                                                    </h3>
                                                    <form method="POST"
                                                          action="{{ route('admin.ads.schedules.update', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id]) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="row align-items-end">
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="form-group">
                                                                    <label for="schedule_screen_{{ $schedule->id }}">
                                                                        {{ __('admin.schedules.form.screen') }}
                                                                        <span class="text-danger" aria-hidden="true">*</span>
                                                                    </label>
                                                                    <select id="schedule_screen_{{ $schedule->id }}"
                                                                            name="screen_id"
                                                                            required
                                                                            class="form-control">
                                                                        @foreach ($availableScreens as $screen)
                                                                            <option value="{{ $screen->id }}"
                                                                                @selected($screen->id == $schedule->screen_id)>
                                                                                {{ $screenLabel($screen) }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="schedule_start_{{ $schedule->id }}">
                                                                        {{ __('admin.schedules.form.start_time') }}
                                                                        <span class="text-danger" aria-hidden="true">*</span>
                                                                    </label>
                                                                    <input type="datetime-local"
                                                                           id="schedule_start_{{ $schedule->id }}"
                                                                           name="start_time"
                                                                           dir="ltr"
                                                                           required
                                                                           value="{{ optional($schedule->start_time)->format('Y-m-d\TH:i') }}"
                                                                           class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="schedule_end_{{ $schedule->id }}">
                                                                        {{ __('admin.schedules.form.end_time') }}
                                                                        <span class="text-danger" aria-hidden="true">*</span>
                                                                    </label>
                                                                    <input type="datetime-local"
                                                                           id="schedule_end_{{ $schedule->id }}"
                                                                           name="end_time"
                                                                           dir="ltr"
                                                                           required
                                                                           value="{{ optional($schedule->end_time)->format('Y-m-d\TH:i') }}"
                                                                           class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-lg-2">
                                                                <div class="form-group">
                                                                    {{-- Same checkbox boolean pattern as the create form:
                                                                         unchecking now submits `is_active=0` instead of
                                                                         nothing, so a schedule can actually be deactivated. --}}
                                                                    <input type="hidden" name="is_active" value="0">
                                                                    <div class="custom-control custom-checkbox">
                                                                        <input type="checkbox"
                                                                               class="custom-control-input"
                                                                               id="schedule_active_{{ $schedule->id }}"
                                                                               name="is_active"
                                                                               value="1"
                                                                               @checked($schedule->is_active)>
                                                                        <label class="custom-control-label"
                                                                               for="schedule_active_{{ $schedule->id }}">
                                                                            {{ __('admin.schedules.form.is_active') }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-12">
                                                                <x-admin.group-btn class="justify-content-end">
                                                                    <button type="button"
                                                                            class="btn btn-light"
                                                                            data-toggle="collapse"
                                                                            data-target="#{{ $editPanelId }}"
                                                                            aria-controls="{{ $editPanelId }}">
                                                                        <i class="fe fe-x" aria-hidden="true"></i>
                                                                        <span>{{ __('admin.schedules.actions.close_edit') }}</span>
                                                                    </button>
                                                                    <x-admin.btn type="submit" icon="save">
                                                                        {{ __('admin.schedules.edit.submit') }}
                                                                    </x-admin.btn>
                                                                </x-admin.group-btn>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endcan
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 5,
                                    'message' => __('admin.schedules.table.empty'),
                                    'icon' => 'calendar',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $schedules, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
