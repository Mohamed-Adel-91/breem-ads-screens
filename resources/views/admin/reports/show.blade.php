@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.reports.index', ['lang' => $lang]);

        $typeLabel = \App\Support\Lang::t(
            'admin.reports.types.' . $report->type,
            ucfirst(str_replace('-', ' ', (string) $report->type))
        );

        // Row layout follows the CANONICAL type, resolved by the controller through
        // App\Support\ReportType. Legacy rows stored as `availability` therefore render
        // with the screen-uptime columns instead of silently falling through to the
        // playback layout, which is what happened before the registry existed.
        $isScreenUptime = $canonicalType === \App\Support\ReportType::SCREEN_UPTIME;
        $isKnownType = $isPresentable;

        // Filters are stored JSON; render them defensively without executing anything.
        $formatFilterValue = function ($value) use (&$formatFilterValue) {
            if (is_array($value)) {
                return collect($value)
                    ->map(function ($item, $key) use (&$formatFilterValue) {
                        $label = is_string($key) ? ucfirst(str_replace('_', ' ', $key)) . ': ' : '';

                        return $label . $formatFilterValue($item);
                    })
                    ->implode(', ');
            }

            if (is_bool($value)) {
                return $value ? __('admin.monitoring.show.yes') : __('admin.monitoring.show.no');
            }

            return (string) $value;
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $report->name,
            'subtitle' => __('admin.reports.show_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_reports'), 'url' => $indexUrl],
                ['label' => $report->name],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.reports.actions.back_to_list'),
                'icon' => 'arrow-left',
            ],
            'primaryAction' => auth('admin')->user()?->can('reports.view')
                ? [
                    'href' => route('admin.reports.download', ['lang' => $lang, 'report' => $report->id]),
                    'label' => __('admin.reports.actions.download_csv'),
                    'icon' => 'download',
                ]
                : null,
        ])

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.reports.show.metadata') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">{{ __('admin.reports.table.type') }}</th>
                                        <td><x-admin.badge variant="light">{{ $typeLabel }}</x-admin.badge></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.reports.show.generated_by') }}</th>
                                        <td>{{ $report->generator?->name ?? __('admin.reports.system') }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.reports.show.created_at') }}</th>
                                        <td dir="ltr">{{ optional($report->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.reports.show.generated_at') }}</th>
                                        <td dir="ltr">{{ data_get($report->data, 'generated_at') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">{{ __('admin.reports.show.total_records') }}</th>
                                        <td>{{ data_get($report->data, 'total_logs', count($rows)) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="card-title mb-0">{{ __('admin.reports.show.applied_filters') }}</h2>
                    </div>
                    <div class="card-body">
                        @if (!empty($report->filters))
                            <div class="admin-actions">
                                @foreach ($report->filters as $key => $value)
                                    <x-admin.badge variant="light" pill>
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                        {{ $formatFilterValue($value) }}
                                    </x-admin.badge>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('admin.reports.show.no_filters') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.reports.show.data_heading') }}</h2>
                <x-admin.badge variant="light">{{ count($rows) }}</x-admin.badge>
            </div>

            @unless ($isKnownType)
                <div class="card-body pb-0">
                    <div class="alert alert-warning mb-0" role="alert">
                        {{ __('admin.reports.show.unknown_type_notice') }}
                    </div>
                </div>
            @endunless

            <div class="card-body p-0">
                <div class="table-responsive">
                    @if ($isScreenUptime)
                        @include('admin.reports.partials.rows-screen-uptime', ['rows' => $rows])
                    @else
                        @include('admin.reports.partials.rows-playback', ['rows' => $rows])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
