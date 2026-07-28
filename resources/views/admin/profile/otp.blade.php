@extends('admin.layouts.master')

@section('title', __('admin.forms.verify_button'))

@section('content')
    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => __('admin.forms.verify_button'),
            'subtitle' => __('admin.forms.otp_prompt'),
            'breadcrumbs' => [
                [
                    'label' => __('admin.pages.profile.edit'),
                    'url' => route('admin.profile.edit', ['lang' => app()->getLocale()]),
                ],
                ['label' => __('admin.forms.verify_button')],
            ],
        ])

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="auth-inline-icon" aria-hidden="true">
                            <i class="fe fe-shield"></i>
                        </div>

                        <form action="{{ route('admin.profile.verifyPasswordOtp', ['lang' => app()->getLocale()]) }}"
                              method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="otp">{{ __('admin.forms.otp_placeholder') }}</label>
                                <input type="text"
                                       id="otp"
                                       name="otp"
                                       inputmode="numeric"
                                       autocomplete="one-time-code"
                                       required
                                       autofocus
                                       value="{{ old('otp') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('otp')])>
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <x-admin.group-btn class="justify-content-end">
                                <x-admin.btn
                                    :href="route('admin.profile.edit', ['lang' => app()->getLocale()])"
                                    variant="light"
                                    icon="arrow-left">
                                    {{ __('admin.buttons.close') }}
                                </x-admin.btn>
                                <x-admin.btn type="submit" icon="check-circle">
                                    {{ __('admin.forms.verify_button') }}
                                </x-admin.btn>
                            </x-admin.group-btn>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
