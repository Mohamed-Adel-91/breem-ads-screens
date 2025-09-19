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
                                <h5 class="mb-0">{{ __('Edit screen') }}</h5>
                                <a href="{{ route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}" class="btn btn-light btn-sm">{{ __('Back to details') }}</a>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.screens.update', ['lang' => $lang, 'screen' => $screen->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.screens.partials.form')
                                    <div class="mt-4 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">{{ __('Update screen') }}</button>
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
