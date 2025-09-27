@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.settings.update', ['lang' => app()->getLocale()]) }}" enctype="multipart/form-data">
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
                                        @forelse ($settings as $setting)
                                            @php
                                                $raw = $setting->getAttribute('value');
                                                $isArray = is_array($raw);
                                                $display = $isArray ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $raw;
                                                $fieldId = 'setting_' . str_replace(['.', ' ', '/'], '_', $setting->key);
                                            @endphp
                                            <div class="form-group col-6">
                                                <label for="{{ $fieldId }}">{{ $setting->key }}</label>
                                                @if ($isArray)
                                                    <textarea class="form-control" id="{{ $fieldId }}" name="settings[{{ $setting->key }}]" rows="4">{{ $display }}</textarea>
                                                @else
                                                    <input type="text" class="form-control" id="{{ $fieldId }}" name="settings[{{ $setting->key }}]" value="{{ $display }}">
                                                @endif
                                            </div>
                                        @empty
                                            <div class="col-12">
                                                <p class="text-muted">{{ __('admin.settings.no_settings') }}</p>
                                            </div>
                                        @endforelse
                                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="text-right">
                                                <button type="submit" id="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
