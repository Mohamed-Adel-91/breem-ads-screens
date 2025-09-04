@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <!-- Side bar area -->
        @include('admin.layouts.sidebar')
        <!-- Side bar area end -->
        <!-- ####################################################################### -->
        <!-- Page content area start -->
        <div class="page-content">
            <!-- Page Header Section start -->
            @include('admin.layouts.page-header')
            <!-- Page Header Section end -->
            <!-- Main container start -->
            <div class="main-container">
                @include('admin.layouts.alerts')
                <!-- Row start -->
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ __('admin.forms.general_data') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="form-group col-4">
                                            <label for="email">{{ __('admin.forms.email') }}</label>
                                            <input type="text" class="form-control" id="email" name="email"
                                                placeholder="{{ __('admin.forms.enter_email_id') }}" value="{{ $data->email }}">
                                            @if ($errors->has('email'))
                                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="hr_mail">{{ __('admin.forms.hr_mail') }}</label>
                                            <input type="text" class="form-control" id="hr_mail" name="hr_mail"
                                                value="{{ $data->hr_mail }}">
                                            @error('hr_mail')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="customer_service_mail">{{ __('admin.forms.customer_service_mail') }}</label>
                                            <input type="text" class="form-control" id="customer_service_mail"
                                                name="customer_service_mail" value="{{ $data->customer_service_mail }}">
                                            @error('customer_service_mail')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="phone">{{ __('admin.forms.phone') }}</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                placeholder="{{ __('admin.forms.enter_phone_number') }}" value="{{ $data->phone }}">
                                            @if ($errors->has('phone'))
                                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="hotline">{{ __('admin.forms.hotline') }}</label>
                                            <input type="text" class="form-control" id="hotline" name="hotline"
                                                placeholder="{{ __('admin.forms.enter_hotline') }}" value="{{ $data->hotline }}">
                                            @if ($errors->has('hotline'))
                                                <span class="text-danger">{{ $errors->first('hotline') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="facebook">{{ __('admin.forms.facebook') }}</label>
                                            <input type="text" class="form-control" id="facebook" name="facebook"
                                                placeholder="{{ __('admin.forms.enter_facebook_url') }}" value="{{ $data->facebook }}">
                                            @if ($errors->has('facebook'))
                                                <span class="text-danger">{{ $errors->first('facebook') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="youtube">{{ __('admin.forms.youtube') }}</label>
                                            <input type="text" class="form-control" id="youtube" name="youtube"
                                                placeholder="{{ __('admin.forms.enter_youtube_url') }}" value="{{ $data->youtube }}">
                                            @if ($errors->has('youtube'))
                                                <span class="text-danger">{{ $errors->first('youtube') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="instagram">{{ __('admin.forms.instagram') }}</label>
                                            <input type="text" class="form-control" id="instagram" name="instagram"
                                                placeholder="{{ __('admin.forms.enter_instagram_url') }}" value="{{ $data->instagram }}">
                                            @if ($errors->has('instagram'))
                                                <span class="text-danger">{{ $errors->first('instagram') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="linkedin">{{ __('admin.forms.linkedin') }}</label>
                                            <input type="text" class="form-control" id="linkedin" name="linkedin"
                                                placeholder="{{ __('admin.forms.enter_linkedin_url') }}" value="{{ $data->linkedin }}">
                                            @if ($errors->has('linkedin'))
                                                <span class="text-danger">{{ $errors->first('linkedin') }}</span>
                                            @endif
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="location">{{ __('admin.forms.map_location') }}</label>
                                            <input type="text" class="form-control" id="location" name="location"
                                                placeholder="{{ __('admin.forms.enter_location_url') }}" value="{{ $data->location }}">
                                            @if ($errors->has('location'))
                                                <span class="text-danger">{{ $errors->first('location') }}</span>
                                            @endif
                                        </div>
                                        <!-- Translatable Fields -->
                                        @php
                                            $languages = ['ar'];
                                        @endphp
                                        @foreach ($languages as $lang)
                                            <div class="form-group col-4">
                                                <label for="slogan_{{ $lang }}">{{ __('admin.forms.slogan') }}</label>
                                                <input type="text" class="form-control"
                                                    id="slogan_{{ $lang }}" name="slogan[{{ $lang }}]"
                                                    placeholder="{{ __('admin.forms.enter_slogan') }}"
                                                    value="{{ $data->getTranslation('slogan', $lang, false) }}">
                                                @if ($errors->has('slogan'))
                                                    <span class="text-danger">{{ $errors->first('slogan') }}</span>
                                                @endif
                                            </div>
                                            <div class="form-group col-4">
                                                <label for="address_{{ $lang }}">{{ __('admin.forms.address') }}</label>
                                                <input type="text" class="form-control"
                                                    id="address_{{ $lang }}" name="address[{{ $lang }}]"
                                                    placeholder="{{ __('admin.forms.enter_address') }}"
                                                    value="{{ $data->getTranslation('address', $lang, false) }}">
                                                @if ($errors->has('address'))
                                                    <span class="text-danger">{{ $errors->first('address') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="text-right">
                                                <button type="submit" id="submit"
                                                    class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- Row end -->
            </div>
            <!-- Main container end -->
        </div>
        <!-- Page content area end -->
    </div>
@endsection
