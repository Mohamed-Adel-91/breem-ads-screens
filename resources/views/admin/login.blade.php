@extends('admin.layouts.auth')

@section('title', __('admin.login.login_button'))

@section('content')
    @php
        $targetLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    @endphp

    <div class="auth-toolbar">
        <a class="auth-language"
           href="{{ route('admin.login', ['lang' => $targetLocale]) }}"
           lang="{{ $targetLocale }}"
           hreflang="{{ $targetLocale }}">
            <i class="fe fe-globe" aria-hidden="true"></i>
            <span>
                {{ app()->getLocale() === 'ar'
                    ? \App\Support\Lang::t('admin.header.english')
                    : \App\Support\Lang::t('admin.header.arabic') }}
            </span>
        </a>
    </div>

    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center py-5">
            <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="card auth-card">
                    <div class="card-body">
                        <a class="auth-logo"
                           href="{{ route('admin.login', ['lang' => app()->getLocale()]) }}"
                           aria-label="Breem">
                            <img src="{{ asset('admin-assets/images/breem-logo.png') }}" alt="Breem">
                        </a>

                        <div class="text-center mb-4">
                            <h1 class="h4 mb-2">{{ __('admin.login.welcome_back') }}</h1>
                            <p class="text-muted mb-0">{{ __('admin.login.login_to_account') }}</p>
                        </div>

                        @include('admin.layouts.alerts')

                        <form action="{{ route('admin.login.attempt', ['lang' => request()->route('lang') ?? app()->getLocale()]) }}"
                              method="POST"
                              novalidate>
                            @csrf

                            <div class="form-group">
                                <label for="email">{{ __('admin.login.email') }}</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       autocomplete="username"
                                       autofocus
                                       required
                                       value="{{ old('email') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('email')])>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password">{{ __('admin.login.password') }}</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="current-password"
                                       required
                                       @class(['form-control', 'is-invalid' => $errors->has('password')])>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <x-admin.btn type="submit" class="btn-block" icon="log-in">
                                {{ __('admin.login.login_button') }}
                            </x-admin.btn>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
