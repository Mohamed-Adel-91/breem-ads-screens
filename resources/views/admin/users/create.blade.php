@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.users.index', ['lang' => $lang]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.users_management'), 'url' => $indexUrl],
                ['label' => __('admin.forms.create')],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.buttons.close'),
                'icon' => 'arrow-left',
            ],
        ])

        <form method="POST" action="{{ route('admin.users.store', ['lang' => $lang]) }}">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ $pageName }}</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">
                                    {{ __('users.full_name') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       required
                                       value="{{ old('name') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('name')])>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nickname">{{ __('users.nickname') }}</label>
                                <input type="text"
                                       id="nickname"
                                       name="nickname"
                                       value="{{ old('nickname') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('nickname')])>
                                @error('nickname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">
                                    {{ __('users.email') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       autocomplete="email"
                                       required
                                       value="{{ old('email') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('email')])>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile">
                                    {{ __('users.mobile') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="mobile"
                                       name="mobile"
                                       autocomplete="tel"
                                       required
                                       value="{{ old('mobile') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('mobile')])>
                                @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">
                                    {{ __('admin.forms.password') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="new-password"
                                       required
                                       @class(['form-control', 'is-invalid' => $errors->has('password')])>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation">
                                    {{ __('admin.forms.password_confirmation') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <x-admin.group-btn class="justify-content-end">
                        <x-admin.btn :href="$indexUrl" variant="light" icon="x">
                            {{ __('admin.buttons.close') }}
                        </x-admin.btn>
                        <x-admin.btn type="submit" icon="save">
                            {{ __('admin.forms.save_button') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>
            </div>
        </form>
    </div>
@endsection
