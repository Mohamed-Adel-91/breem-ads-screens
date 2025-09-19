@extends('admin.layouts.master')
@section('content')
    @php
        $primaryLocale = config('app.locale');
    @endphp
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.cms.contact.update', ['lang' => app()->getLocale()]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Top Banner</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => 'Banner Image',
                                'name' => 'banner[image]',
                                'inputId' => 'contact_banner_image',
                                'previewPath' => media_path($bannerData[$primaryLocale]['image_path'] ?? ''),
                            ])
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Contact Heading</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($locales as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="contact[{{ $locale }}][title]"
                                            value="{{ old("contact.$locale.title", $contactData[$locale]['title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Subtitle ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="contact[{{ $locale }}][subtitle]"
                                            value="{{ old("contact.$locale.subtitle", $contactData[$locale]['subtitle'] ?? '') }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Map & Location</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    @include('admin.layouts.components.media-upload', [
                                        'label' => 'Background Image',
                                        'name' => 'map[background_image]',
                                        'inputId' => 'contact_map_background',
                                        'previewPath' => media_path($mapData[$primaryLocale]['background_image_path'] ?? ''),
                                    ])
                                </div>
                                @foreach ($locales as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="map[{{ $locale }}][title]"
                                            value="{{ old("map.$locale.title", $mapData[$locale]['title'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control" rows="3" name="map[{{ $locale }}][address]">{{ old("map.$locale.address", $mapData[$locale]['address'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone label ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="map[{{ $locale }}][phone_label]"
                                            value="{{ old("map.$locale.phone_label", $mapData[$locale]['phone_label'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">WhatsApp label ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="map[{{ $locale }}][whatsapp_label]"
                                            value="{{ old("map.$locale.whatsapp_label", $mapData[$locale]['whatsapp_label'] ?? '') }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Bottom Banner</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => 'Banner Image',
                                'name' => 'bottom[image]',
                                'inputId' => 'contact_bottom_image',
                                'previewPath' => media_path($bottomData[$primaryLocale]['image_path'] ?? ''),
                            ])
                        </div>
                    </div>

                    @php
                        $forms = [
                            'ads' => ['title' => 'Ads Subscription Form', 'data' => $adsData, 'form' => $adsForm],
                            'screens' => ['title' => 'Screens Subscription Form', 'data' => $screensData, 'form' => $screensForm],
                            'create' => ['title' => 'Ad Creation Request', 'data' => $createData, 'form' => $createForm],
                            'faq' => ['title' => 'FAQs Form', 'data' => $faqData, 'form' => $faqForm],
                        ];
                    @endphp

                    @foreach ($forms as $key => $payload)
                        @php $sectionData = $payload['data']; @endphp
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">{{ $payload['title'] }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => 'Card Image 1',
                                            'name' => "contact_forms[$key][card_image1]",
                                            'inputId' => "contact_{$key}_image1",
                                            'previewPath' => media_path($sectionData[$primaryLocale]['card_image1'] ?? ''),
                                        ])
                                    </div>
                                    <div class="col-md-6">
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => 'Card Image 2',
                                            'name' => "contact_forms[$key][card_image2]",
                                            'inputId' => "contact_{$key}_image2",
                                            'previewPath' => media_path($sectionData[$primaryLocale]['card_image2'] ?? ''),
                                        ])
                                    </div>
                                </div>
                                <div class="row g-3 mt-0">
                                    @foreach ($locales as $locale)
                                        <div class="col-md-6">
                                            <label class="form-label">Card Text ({{ strtoupper($locale) }})</label>
                                            <textarea class="form-control" rows="3" name="contact_forms[{{ $key }}][{{ $locale }}][card_text]">{{ old("contact_forms.$key.$locale.card_text", $sectionData[$locale]['card_text'] ?? '') }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Modal Title ({{ strtoupper($locale) }})</label>
                                            <input type="text" class="form-control" name="contact_forms[{{ $key }}][{{ $locale }}][modal_title]"
                                                value="{{ old("contact_forms.$key.$locale.modal_title", $sectionData[$locale]['modal_title'] ?? '') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Submit Text ({{ strtoupper($locale) }})</label>
                                            <input type="text" class="form-control" name="contact_forms[{{ $key }}][{{ $locale }}][submit_text]"
                                                value="{{ old("contact_forms.$key.$locale.submit_text", $sectionData[$locale]['submit_text'] ?? '') }}">
                                        </div>

                                        @php
                                            $labels = $sectionData[$locale]['labels'] ?? [];
                                        @endphp
                                        @if (!empty($labels))
                                            <div class="col-12">
                                                <div class="row g-3">
                                                    @foreach ($labels as $labelKey => $labelValue)
                                                        <div class="col-md-6">
                                                            <label class="form-label">Label {{ strtoupper($locale) }} - {{ \Illuminate\Support\Str::headline($labelKey) }}</label>
                                                            <input type="text" class="form-control"
                                                                name="contact_forms[{{ $key }}][{{ $locale }}][labels][{{ $labelKey }}]"
                                                                value="{{ old("contact_forms.$key.$locale.labels.$labelKey", $labelValue) }}">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @php $radio = $sectionData[$locale]['radio'] ?? []; @endphp
                                        @if (!empty($radio))
                                            <div class="col-12">
                                                <div class="row g-3">
                                                    @foreach ($radio as $radioKey => $radioValue)
                                                        <div class="col-md-6">
                                                            <label class="form-label">Radio {{ strtoupper($locale) }} - {{ \Illuminate\Support\Str::headline($radioKey) }}</label>
                                                            <input type="text" class="form-control"
                                                                name="contact_forms[{{ $key }}][{{ $locale }}][radio][{{ $radioKey }}]"
                                                                value="{{ old("contact_forms.$key.$locale.radio.$radioKey", $radioValue) }}">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @php $options = $sectionData[$locale]['options'] ?? []; @endphp
                                        @if (!empty($options))
                                            <div class="col-12">
                                                <div class="row g-3">
                                                    @foreach ($options as $optionKey => $optionValues)
                                                        <div class="col-md-6">
                                                            <label class="form-label">Options {{ strtoupper($locale) }} - {{ \Illuminate\Support\Str::headline($optionKey) }}</label>
                                                            <textarea class="form-control" rows="3" name="contact_forms[{{ $key }}][{{ $locale }}][options][{{ $optionKey }}]">{{ old("contact_forms.$key.$locale.options.$optionKey", implode("\n", $optionValues ?? [])) }}</textarea>
                                                            <small class="text-muted">Enter each option on a separate line.</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
