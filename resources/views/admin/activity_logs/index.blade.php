@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <div class="row gutters">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="table-container">
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.logs.index', ['lang' => $lang]),
                                'resetUrl' => route('admin.logs.index', ['lang' => $lang]),
                                'exportUrl' => route('admin.logs.download', array_merge(['lang' => $lang], request()->query())),
                                'checkboxes' => ['today' => __('admin.activity_logs.filters.today_only')],
                                'filters' => $filters,
                            ])
                            @include('admin.partials.results-summary', ['data' => $data, 'label' => \App\Support\Lang::t('admin.activity_logs.results_label', 'log(s)')])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('admin.activity_logs.table.description') }}</th>
                                            <th>{{ __('admin.activity_logs.table.causer') }}</th>
                                            <th class="w-50">{{ __('admin.activity_logs.table.properties') }}</th>
                                            <th>{{ __('admin.table.created_at') }}</th>
                                            <th>{{ __('admin.table.updated_at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->description ?? '-' }}</td>
                                                <td>
                                                    <strong>{{ __('admin.activity_logs.labels.user') }}</strong> {{ optional($item->causer)->first_name ?? '-' }}
                                                    {{ optional($item->causer)->last_name ?? '-' }}
                                                    <br>
                                                    <strong>{{ __('admin.activity_logs.labels.email') }}</strong> {{ optional($item->causer)->email ?? '-' }}
                                                </td>
                                                <td>
                                                    @if ($item->properties && $item->properties->count())
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach ($item->properties as $key => $value)
                                                                <li>
                                                                    <strong>{{ \Illuminate\Support\Str::headline($key) }}:</strong>
                                                                    @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                                                                        {{ implode(', ', (array) $value) }}
                                                                    @else
                                                                        {{ $value }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        {{ __('admin.activity_logs.messages.empty') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination', ['data' => $data])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
