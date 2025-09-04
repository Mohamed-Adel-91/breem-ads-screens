@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <!-- Side bar area -->
        @include('admin.layouts.sidebar')
        <!-- Side bar area end -->
        <!-- ####################################################################### -->
        <!-- Page content area start -->
        <div class="page-content">
            <!-- Page Header Section start -->
            @include('admin.layouts.page-header')
            <!-- Page Header Section end -->
            <!-- Main container start -->
            <div class="main-container">
                @include('admin.layouts.alerts')
                <!-- Row start -->
                <div class="row gutters">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="table-container">
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.newsletter.index'),
                                'resetUrl' => route('admin.newsletter.index'),
                                'exportUrl' => route('admin.newsletter.download'),
                                'filters' => $filters,
                                'checkboxes' => ['today' => 'نتائج اليوم فقط'],
                            ])
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'subscriber(s)',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>تاريخ التحديث</th>
                                            <th>إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->email ?? '-' }}</td>
                                                    <td>{{ $item->created_at ?? '-' }}</td>
                                                    <td>{{ $item->updated_at ?? '-' }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="mailto:{{ $item->email }}" class="icon bg-info"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Send Email">
                                                                <i class="icon-email"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        لا توجد سجلات للاشتراكات مطابقة للمعايير.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination')
                        </div>
                    </div>
                </div>
                <!-- Row end -->
            </div>
            <!-- Main container end -->
        </div>
        <!-- Page content area end -->
    </div>
@endsection
