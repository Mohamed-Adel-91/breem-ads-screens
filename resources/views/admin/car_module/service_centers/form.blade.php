@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ $data?->id ? route('admin.service_centers.update', $data->id) : route('admin.service_centers.store') }}">
                    @csrf
                    @if ($data?->id)
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ $data?->id ? __('admin.forms.edit') : __('admin.forms.create') }} {{ __('admin.forms.service_center_entity') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="brand_id">{{ __('admin.forms.brand') }}</label>
                                                <select class="form-control" id="brand_id" name="brand_id">
                                                    <option value="">{{ __('admin.forms.choose_brand') }}</option>
                                                    @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}" {{ old('brand_id', $data->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('brand_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('admin.forms.name') }}</label>
                                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="location">{{ __('admin.forms.location') }}</label>
                                                <input type="text" class="form-control" id="location" name="location" value="{{ old('location', $data->location ?? '') }}">
                                                @error('location')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address">{{ __('admin.forms.address') }}</label>
                                                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $data->address ?? '') }}">
                                                @error('address')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone">{{ __('admin.forms.phone') }}</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $data->phone ?? '') }}">
                                                @error('phone')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="city">{{ __('admin.forms.city') }}</label>
                                                <select class="form-control" id="city" name="city">
                                                    <option value="">{{ __('admin.forms.choose_city') }}</option>
                                                    @foreach ($cities as $key => $city)
                                                        <option value="{{ $key }}" {{ old('city', optional($data->city)->value ?? '') == $key ? 'selected' : '' }}>{{ $city }}</option>
                                                    @endforeach
                                                </select>
                                                @error('city')
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
