@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <!-- Page content area start -->
        <div class="container">
            <form action="{{ route('admin.login') }}" method="POST">
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
                                    <input type="email" name="email" autocomplete="username" class="form-control" value="{{ old('email') }}" placeholder="{{ __('admin.login.email') }}" />
                                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control" placeholder="{{ __('admin.login.password') }}" />
                                    @error('password') <span class="text-danger">{{ $message }}</span> @enderror
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
