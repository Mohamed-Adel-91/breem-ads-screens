@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.users.store', ['lang' => $lang]) }}">
                    @csrf
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">
                                        {{ $pageName }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('users.full_name') }}</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="{{ old('name') }}" required>
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nickname">{{ __('users.nickname') }}</label>
                                                <input type="text" class="form-control" id="nickname" name="nickname"
                                                    value="{{ old('nickname') }}">
                                                @error('nickname')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">{{ __('users.email') }}</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="{{ old('email') }}" required>
                                                @error('email')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="mobile">{{ __('users.mobile') }}</label>
                                                <input type="text" class="form-control" id="mobile" name="mobile"
                                                    value="{{ old('mobile') }}" required>
                                                @error('mobile')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">{{ __('admin.forms.password') }}</label>
                                                <input type="password" class="form-control" id="password" name="password"
                                                    required>
                                                @error('password')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password_confirmation">{{ __('admin.forms.password_confirmation') }}</label>
                                                <input type="password" class="form-control" id="password_confirmation"
                                                    name="password_confirmation" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-end">
                                    <a href="{{ route('admin.users.index', ['lang' => $lang]) }}"
                                        class="btn btn-light me-2">{{ __('admin.buttons.close') }}</a>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('admin.forms.save_button') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
