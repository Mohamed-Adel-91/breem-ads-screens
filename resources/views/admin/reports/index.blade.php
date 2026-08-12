@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.reports.index', ['lang' => $lang]);

        // Persisted type values are emitted verbatim; only the label is translated.
        $typeLabel = fn (?string $type) => $type
            ? \App\Support\Lang::t('admin.reports.types.' . $type, ucfirst(str_replace('-', ' ', $type)))
            : null;

        $screenLabel = function ($screen) {
            $label = $screen->code;

            if ($screen->place) {
                $placeName = data_get($screen->place->getTranslations('name'), app()->getLocale());

                if ($placeName) {
                    $label .= ' — ' . $placeName;
                }
            }

            return $label;
        };

        $adTitle = fn ($ad) => data_get($ad->getTranslations('title'), app()->getLocale())
            ?: __('admin.reports.unnamed_ad', ['id' => $ad->id]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.reports.subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_reports')],
            ],
        ])

        {{-- Filter card: query parameter names (`search`, `type`) preserved verbatim. --}}
        <div class="card admin-filter-card mb-4">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.reports.filters.heading') }}</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $indexUrl }}">
                    <div class="row align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <div class="form-group">
                                <label for="search">{{ __('admin.reports.filters.search') }}</label>
                                <input type="text"
                                       id="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="{{ __('admin.reports.filters.search_placeholder') }}"
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <div class="form-group">
                                <label for="type">{{ __('admin.reports.filters.type') }}</label>
                                <select id="type" name="type" class="form-control">
                                    <option value="">{{ __('admin.reports.filters.all_types') }}</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>
                                            {{ $typeLabel($type) }}
                                        </option>
                                    @endforeach
                                </select>
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

        @can('reports.generate')
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.reports.generate.heading') }}</h2>
                </div>
                <div class="card-body">
                    {{-- Field names (`name`, `type`, `screen_id`, `ad_id`, `from_date`,
                         `to_date`) and the persisted `type` values are unchanged. --}}
                    <form method="POST" action="{{ route('admin.reports.generate', ['lang' => $lang]) }}">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="name">
                                        {{ __('admin.reports.generate.name') }}
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text"
                                           id="name"
                                           name="name"
                                           required
                                           placeholder="{{ __('admin.reports.generate.name_placeholder') }}"
                                           value="{{ old('name') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('name')])>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <label for="type_select">
                                        {{ __('admin.reports.generate.type') }}
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <select id="type_select"
                                            name="type"
                                            required
                                            @class(['form-control', 'is-invalid' => $errors->has('type')])>
                                        @foreach ($types as $type)
                                            <option value="{{ $type }}"
                                                @selected(old('type', $types[0] ?? '') === $type)>
                                                {{ $typeLabel($type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="screen_id">{{ __('admin.reports.generate.screen') }}</label>
                                    <select id="screen_id"
                                            name="screen_id"
                                            @class(['form-control', 'is-invalid' => $errors->has('screen_id')])>
                                        <option value="">{{ __('admin.reports.generate.all_screens') }}</option>
                                        @foreach ($screens as $screen)
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
                                    <label for="ad_id">{{ __('admin.reports.generate.ad') }}</label>
                                    <select id="ad_id"
                                            name="ad_id"
                                            @class(['form-control', 'is-invalid' => $errors->has('ad_id')])>
                                        <option value="">{{ __('admin.reports.generate.all_ads') }}</option>
                                        @foreach ($ads as $ad)
                                            <option value="{{ $ad->id }}" @selected(old('ad_id') == $ad->id)>
                                                {{ $adTitle($ad) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('ad_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="from_date">{{ __('admin.reports.generate.from_date') }}</label>
                                    <input type="date"
                                           id="from_date"
                                           name="from_date"
                                           dir="ltr"
                                           value="{{ old('from_date') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('from_date')])>
                                    @error('from_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="to_date">{{ __('admin.reports.generate.to_date') }}</label>
                                    <input type="date"
                                           id="to_date"
                                           name="to_date"
                                           dir="ltr"
                                           value="{{ old('to_date') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('to_date')])>
                                    @error('to_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <x-admin.group-btn class="justify-content-end">
                                    <x-admin.btn type="submit" variant="success" icon="bar-chart-2">
                                        {{ __('admin.reports.generate.submit') }}
                                    </x-admin.btn>
                                </x-admin.group-btn>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        @include('admin.partials.results-summary', [
            'data' => $reports,
            'label' => __('admin.reports.results_label'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.reports.table.heading') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.reports.table.name') }}</th>
                                <th scope="col">{{ __('admin.reports.table.type') }}</th>
                                <th scope="col">{{ __('admin.reports.table.generated_by') }}</th>
                                <th scope="col">{{ __('admin.reports.table.created_at') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <th scope="row">{{ ($reports->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>{{ $report->name }}</td>
                                    <td>
                                        <x-admin.badge variant="light">{{ $typeLabel($report->type) }}</x-admin.badge>
                                    </td>
                                    <td>{{ $report->generator?->name ?? __('admin.reports.system') }}</td>
                                    <td dir="ltr">{{ optional($report->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('reports.view')
                                                <x-admin.btn
                                                    :href="route('admin.reports.show', ['lang' => $lang, 'report' => $report->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.reports.actions.view') }}
                                                </x-admin.btn>
                                                <x-admin.btn
                                                    :href="route('admin.reports.download', ['lang' => $lang, 'report' => $report->id])"
                                                    variant="outline-primary"
                                                    size="sm"
                                                    icon="download">
                                                    {{ __('admin.reports.actions.download') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 6,
                                    'message' => __('admin.reports.table.empty'),
                                    'icon' => 'bar-chart-2',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $reports, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
