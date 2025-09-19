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
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ __('Create place') }}</h5>
                                <a href="{{ route('admin.places.index', ['lang' => $lang]) }}" class="btn btn-light btn-sm">{{ __('Back to list') }}</a>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.places.store', ['lang' => $lang]) }}">
                                    @csrf
                                    @include('admin.places.partials.form')
                                    <div class="mt-4 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">{{ __('Save place') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
