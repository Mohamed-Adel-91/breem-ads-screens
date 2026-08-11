@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $locale = app()->getLocale();
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => $adminName,
            'breadcrumbs' => [
                ['label' => __('admin.header.my_profile')],
            ],
        ])

        <div class="row">
            <div class="col-lg-7">
                <form method="POST"
                      action="{{ route('admin.profile.update', ['lang' => $locale]) }}"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title mb-0">{{ __('admin.forms.update_profile') }}</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">
                                            {{ __('admin.forms.first_name') }}
                                            <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input type="text"
                                               id="first_name"
                                               name="first_name"
                                               required
                                               autocomplete="given-name"
                                               value="{{ old('first_name', $admin->first_name) }}"
                                               @class(['form-control', 'is-invalid' => $errors->has('first_name')])>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">
                                            {{ __('admin.forms.last_name') }}
                                            <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input type="text"
                                               id="last_name"
                                               name="last_name"
                                               required
                                               autocomplete="family-name"
                                               value="{{ old('last_name', $admin->last_name) }}"
                                               @class(['form-control', 'is-invalid' => $errors->has('last_name')])>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_email">{{ __('admin.forms.email') }}</label>
                                        {{-- Read-only on purpose: the profile endpoint does not accept an email change. --}}
                                        <input type="email"
                                               id="profile_email"
                                               class="form-control"
                                               value="{{ $admin->email }}"
                                               aria-describedby="profile_email_help"
                                               readonly
                                               disabled>
                                        <small id="profile_email_help" class="form-text text-muted">
                                            {{ __('admin.forms.email_readonly_hint') }}
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">{{ __('admin.forms.mobile') }}</label>
                                        <input type="text"
                                               id="mobile"
                                               name="mobile"
                                               autocomplete="tel"
                                               value="{{ old('mobile', $admin->mobile) }}"
                                               @class(['form-control', 'is-invalid' => $errors->has('mobile')])>
                                        @error('mobile')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <x-admin.image-uploader
                                        name="profile_picture"
                                        :label="__('admin.forms.profile_picture')"
                                        :preview-url="$admin->image_path"
                                        :old-file="$admin->profile_picture"
                                        size-hint="(680×440)" />
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <x-admin.group-btn class="justify-content-end">
                                <x-admin.btn type="submit" icon="save">
                                    {{ __('admin.forms.save_button') }}
                                </x-admin.btn>
                            </x-admin.group-btn>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-5">
                <form method="POST" action="{{ route('admin.profile.updatePassword', ['lang' => $locale]) }}">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title mb-0">{{ __('admin.forms.change_password_button') }}</h2>
                        </div>
                        <div class="card-body">
                            <div class="auth-inline-icon" aria-hidden="true">
                                <i class="fe fe-shield"></i>
                            </div>

                            <p class="text-muted small">{{ __('admin.forms.password_otp_notice') }}</p>

                            <div class="form-group">
                                <label for="current_password">
                                    {{ __('admin.forms.current_password') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       required
                                       autocomplete="current-password"
                                       @class(['form-control', 'is-invalid' => $errors->has('current_password')])>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    {{ __('admin.forms.new_password') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       required
                                       autocomplete="new-password"
                                       aria-describedby="new_password_help"
                                       @class(['form-control', 'is-invalid' => $errors->has('password')])>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="new_password_help" class="form-text text-muted">
                                    {{ __('admin.forms.password_min_hint') }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">
                                    {{ __('admin.forms.confirm_new_password') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       required
                                       autocomplete="new-password"
                                       class="form-control">
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <x-admin.group-btn class="justify-content-end">
                                <x-admin.btn type="submit" icon="key">
                                    {{ __('admin.forms.change_password_button') }}
                                </x-admin.btn>
                            </x-admin.group-btn>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
