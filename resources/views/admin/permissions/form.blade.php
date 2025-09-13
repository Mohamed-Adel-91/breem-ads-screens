@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data)
                        ? route('admin.permissions.update', ['permission' => $data->id, 'lang' => app()->getLocale()])
                        : route('admin.permissions.store', ['lang' => app()->getLocale()]) }}">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">
                                        {{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }}
                                        {{ __('admin.sidebar.permissions') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('admin.forms.route') }}</label>
                                                <select class="form-control" id="name" name="name" required>
                                                    <option value="">{{ __('admin.forms.choose_route') }}</option>
                                                    @foreach ($routes as $route)
                                                        <option value="{{ $route }}"
                                                            {{ old('name', $data->name ?? '') == $route ? 'selected' : '' }}>
                                                            {{ $route }}</option>
                                                    @endforeach
                                                </select>
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

