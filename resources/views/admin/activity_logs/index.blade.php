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
                                'action' => route('admin.activity_logs.index'),
                                'resetUrl' => route('admin.activity_logs.index'),
                                'exportUrl' => route('admin.activity_logs.download', request()->query()),
                                'checkboxes' => ['today' =>'نتائج اليوم فقط'],
                                'filters' => $filters,
                            ])
                            @include('admin.partials.results-summary', ['data' => $data, 'label' => 'log(s)'])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الوصف</th>
                                            <th>الفاعل</th>
                                            <th class="w-50">الخصائص</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>تاريخ التحديث</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->description ?? '-' }}</td>
                                                <td>
                                                    <strong>المستخدم:</strong> {{ optional($item->causer)->first_name ?? '-' }}
                                                    {{ optional($item->causer)->last_name ?? '-' }}
                                                    <br>
                                                    <strong>البريد الإلكتروني:</strong> {{ optional($item->causer)->email ?? '-' }}
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
                                                        لا توجد سجلات لسجل النشاط مطابقة للمعايير.
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
