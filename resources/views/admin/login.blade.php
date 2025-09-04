@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <!-- Page content area start -->
        <div class="container">
            <form action="{{ route('admin.login') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row justify-content-md-center">
                    <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                        <div class="login-screen">
                            <div class="login-box">
                                <div>
                                    <img class="w-100 mb-4" src="{{ asset('logo.png') }}">
                                </div>
                                <h5>{{ __('admin.login.welcome_back') }}<br />{{ __('admin.login.login_to_account') }}</h5>
                                @include('admin.layouts.alerts')
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control" placeholder="{{ __('admin.login.email') }}" />
                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control" placeholder="{{ __('admin.login.password') }}" />
                                    @if ($errors->has('password'))
                                        <span class="text-danger">{{ $errors->first('password') }}</span>
                                    @endif
                                </div>
                                <div class="actions mb-4">
                                    <button type="submit" class="btn btn-primary">{{ __('admin.login.login_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
