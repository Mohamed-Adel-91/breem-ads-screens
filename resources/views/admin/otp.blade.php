@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <div class="container">
            <form action="{{ route('admin.verifyOtp') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row justify-content-md-center">
                    <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                        <div class="login-screen">
                            <div class="login-box">
                                <h5>{{ __('admin.forms.otp_prompt') }}</h5>
                                @include('admin.layouts.alerts')
                                <div class="form-group">
                                    <input type="text" name="otp" class="form-control" placeholder="{{ __('admin.forms.otp_placeholder') }}" />
                                </div>
                                <div class="actions mb-4">
                                    <button type="submit" class="btn btn-primary">{{ __('admin.forms.verify_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
