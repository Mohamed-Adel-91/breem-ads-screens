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
                    <div class="col-12">
                        <h2> مرحبا بك  ...!!</h2>
                    </div>
                </div>
                <!-- Row end -->
            </div>
            <!-- Main container end -->
        </div>
        <!-- Page content area end -->
    </div>
@endsection
@push('custom-css-scripts')
    <style>
        .req-p {
            font-size: 12px;
            color: #000 !important;
        }
    </style>
@endpush
