@extends('admin.layouts.master')
@section('content')
    @php
        $primaryLocale = config('app.locale');
        $arLocale = 'ar';
        $enLocale = 'en';
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
                            @php
                                $contactAr = $contactData[$arLocale] ?? [];
                                $contactEn = $contactData[$enLocale] ?? [];
                            @endphp
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">Title ({{ strtoupper($arLocale) }})</label>
                                    <input type="text" class="form-control" dir="rtl"
                                        name="contact[{{ $arLocale }}][title]"
                                        value="{{ old("contact.$arLocale.title", $contactAr['title'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title ({{ strtoupper($enLocale) }})</label>
                                    <input type="text" class="form-control" name="contact[{{ $enLocale }}][title]"
                                        value="{{ old("contact.$enLocale.title", $contactEn['title'] ?? '') }}">
                                </div>
                            </div>
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">Subtitle ({{ strtoupper($arLocale) }})</label>
                                    <input type="text" class="form-control" dir="rtl"
                                        name="contact[{{ $arLocale }}][subtitle]"
                                        value="{{ old("contact.$arLocale.subtitle", $contactAr['subtitle'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subtitle ({{ strtoupper($enLocale) }})</label>
                                    <input type="text" class="form-control" name="contact[{{ $enLocale }}][subtitle]"
                                        value="{{ old("contact.$enLocale.subtitle", $contactEn['subtitle'] ?? '') }}">
                                </div>
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
                            </div>
                            @php
                                $mapAr = $mapData[$arLocale] ?? [];
                                $mapEn = $mapData[$enLocale] ?? [];
                            @endphp
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">Title ({{ strtoupper($arLocale) }})</label>
                                    <input type="text" class="form-control" dir="rtl" name="map[{{ $arLocale }}][title]"
                                        value="{{ old("map.$arLocale.title", $mapAr['title'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title ({{ strtoupper($enLocale) }})</label>
                                    <input type="text" class="form-control" name="map[{{ $enLocale }}][title]"
                                        value="{{ old("map.$enLocale.title", $mapEn['title'] ?? '') }}">
                                </div>
                            </div>
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">Address ({{ strtoupper($arLocale) }})</label>
                                    <textarea class="form-control" rows="3" dir="rtl" name="map[{{ $arLocale }}][address]">{{ old("map.$arLocale.address", $mapAr['address'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address ({{ strtoupper($enLocale) }})</label>
                                    <textarea class="form-control" rows="3" name="map[{{ $enLocale }}][address]">{{ old("map.$enLocale.address", $mapEn['address'] ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">Phone label ({{ strtoupper($arLocale) }})</label>
                                    <input type="text" class="form-control" dir="rtl"
                                        name="map[{{ $arLocale }}][phone_label]"
                                        value="{{ old("map.$arLocale.phone_label", $mapAr['phone_label'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone label ({{ strtoupper($enLocale) }})</label>
                                    <input type="text" class="form-control" name="map[{{ $enLocale }}][phone_label]"
                                        value="{{ old("map.$enLocale.phone_label", $mapEn['phone_label'] ?? '') }}">
                                </div>
                            </div>
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6 text-md-end">
                                    <label class="form-label text-md-end d-block">WhatsApp label ({{ strtoupper($arLocale) }})</label>
                                    <input type="text" class="form-control" dir="rtl"
                                        name="map[{{ $arLocale }}][whatsapp_label]"
                                        value="{{ old("map.$arLocale.whatsapp_label", $mapAr['whatsapp_label'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp label ({{ strtoupper($enLocale) }})</label>
                                    <input type="text" class="form-control" name="map[{{ $enLocale }}][whatsapp_label]"
                                        value="{{ old("map.$enLocale.whatsapp_label", $mapEn['whatsapp_label'] ?? '') }}">
                                </div>
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
                                @php
                                    $sectionAr = $sectionData[$arLocale] ?? [];
                                    $sectionEn = $sectionData[$enLocale] ?? [];

                                    $labelKeys = array_keys($sectionAr['labels'] ?? []);
                                    foreach (array_keys($sectionEn['labels'] ?? []) as $labelKey) {
                                        if (!in_array($labelKey, $labelKeys, true)) {
                                            $labelKeys[] = $labelKey;
                                        }
                                    }
                                    $oldLabelsAr = old("contact_forms.$key.$arLocale.labels", []);
                                    if (is_array($oldLabelsAr)) {
                                        foreach (array_keys($oldLabelsAr) as $labelKey) {
                                            if (!in_array($labelKey, $labelKeys, true)) {
                                                $labelKeys[] = $labelKey;
                                            }
                                        }
                                    }
                                    $oldLabelsEn = old("contact_forms.$key.$enLocale.labels", []);
                                    if (is_array($oldLabelsEn)) {
                                        foreach (array_keys($oldLabelsEn) as $labelKey) {
                                            if (!in_array($labelKey, $labelKeys, true)) {
                                                $labelKeys[] = $labelKey;
                                            }
                                        }
                                    }

                                    $radioKeys = array_keys($sectionAr['radio'] ?? []);
                                    foreach (array_keys($sectionEn['radio'] ?? []) as $radioKey) {
                                        if (!in_array($radioKey, $radioKeys, true)) {
                                            $radioKeys[] = $radioKey;
                                        }
                                    }
                                    $oldRadioAr = old("contact_forms.$key.$arLocale.radio", []);
                                    if (is_array($oldRadioAr)) {
                                        foreach (array_keys($oldRadioAr) as $radioKey) {
                                            if (!in_array($radioKey, $radioKeys, true)) {
                                                $radioKeys[] = $radioKey;
                                            }
                                        }
                                    }
                                    $oldRadioEn = old("contact_forms.$key.$enLocale.radio", []);
                                    if (is_array($oldRadioEn)) {
                                        foreach (array_keys($oldRadioEn) as $radioKey) {
                                            if (!in_array($radioKey, $radioKeys, true)) {
                                                $radioKeys[] = $radioKey;
                                            }
                                        }
                                    }

                                    $optionKeys = array_keys($sectionAr['options'] ?? []);
                                    foreach (array_keys($sectionEn['options'] ?? []) as $optionKey) {
                                        if (!in_array($optionKey, $optionKeys, true)) {
                                            $optionKeys[] = $optionKey;
                                        }
                                    }
                                    $oldOptionsAr = old("contact_forms.$key.$arLocale.options", []);
                                    if (is_array($oldOptionsAr)) {
                                        foreach (array_keys($oldOptionsAr) as $optionKey) {
                                            if (!in_array($optionKey, $optionKeys, true)) {
                                                $optionKeys[] = $optionKey;
                                            }
                                        }
                                    }
                                    $oldOptionsEn = old("contact_forms.$key.$enLocale.options", []);
                                    if (is_array($oldOptionsEn)) {
                                        foreach (array_keys($oldOptionsEn) as $optionKey) {
                                            if (!in_array($optionKey, $optionKeys, true)) {
                                                $optionKeys[] = $optionKey;
                                            }
                                        }
                                    }
                                @endphp

                                <div class="row g-3 flex-md-row-reverse mt-3">
                                    <div class="col-md-6 text-md-end">
                                        <label class="form-label text-md-end d-block">Card Text ({{ strtoupper($arLocale) }})</label>
                                        <textarea class="form-control" rows="3" dir="rtl" name="contact_forms[{{ $key }}][{{ $arLocale }}][card_text]">{{ old("contact_forms.$key.$arLocale.card_text", $sectionAr['card_text'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Card Text ({{ strtoupper($enLocale) }})</label>
                                        <textarea class="form-control" rows="3" name="contact_forms[{{ $key }}][{{ $enLocale }}][card_text]">{{ old("contact_forms.$key.$enLocale.card_text", $sectionEn['card_text'] ?? '') }}</textarea>
                                    </div>
                                </div>
                                <div class="row g-3 flex-md-row-reverse">
                                    <div class="col-md-6 text-md-end">
                                        <label class="form-label text-md-end d-block">Modal Title ({{ strtoupper($arLocale) }})</label>
                                        <input type="text" class="form-control" dir="rtl"
                                            name="contact_forms[{{ $key }}][{{ $arLocale }}][modal_title]"
                                            value="{{ old("contact_forms.$key.$arLocale.modal_title", $sectionAr['modal_title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Modal Title ({{ strtoupper($enLocale) }})</label>
                                        <input type="text" class="form-control"
                                            name="contact_forms[{{ $key }}][{{ $enLocale }}][modal_title]"
                                            value="{{ old("contact_forms.$key.$enLocale.modal_title", $sectionEn['modal_title'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="row g-3 flex-md-row-reverse">
                                    <div class="col-md-6 text-md-end">
                                        <label class="form-label text-md-end d-block">Submit Text ({{ strtoupper($arLocale) }})</label>
                                        <input type="text" class="form-control" dir="rtl"
                                            name="contact_forms[{{ $key }}][{{ $arLocale }}][submit_text]"
                                            value="{{ old("contact_forms.$key.$arLocale.submit_text", $sectionAr['submit_text'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Submit Text ({{ strtoupper($enLocale) }})</label>
                                        <input type="text" class="form-control"
                                            name="contact_forms[{{ $key }}][{{ $enLocale }}][submit_text]"
                                            value="{{ old("contact_forms.$key.$enLocale.submit_text", $sectionEn['submit_text'] ?? '') }}">
                                    </div>
                                </div>

                                @if (!empty($labelKeys))
                                    @foreach ($labelKeys as $labelKey)
                                        <div class="row g-3 flex-md-row-reverse">
                                            <div class="col-md-6 text-md-end">
                                                <label class="form-label text-md-end d-block">Label {{ strtoupper($arLocale) }} - {{ \Illuminate\Support\Str::headline($labelKey) }}</label>
                                                <input type="text" class="form-control" dir="rtl"
                                                    name="contact_forms[{{ $key }}][{{ $arLocale }}][labels][{{ $labelKey }}]"
                                                    value="{{ old("contact_forms.$key.$arLocale.labels.$labelKey", $sectionAr['labels'][$labelKey] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Label {{ strtoupper($enLocale) }} - {{ \Illuminate\Support\Str::headline($labelKey) }}</label>
                                                <input type="text" class="form-control"
                                                    name="contact_forms[{{ $key }}][{{ $enLocale }}][labels][{{ $labelKey }}]"
                                                    value="{{ old("contact_forms.$key.$enLocale.labels.$labelKey", $sectionEn['labels'][$labelKey] ?? '') }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (!empty($radioKeys))
                                    @foreach ($radioKeys as $radioKey)
                                        <div class="row g-3 flex-md-row-reverse">
                                            <div class="col-md-6 text-md-end">
                                                <label class="form-label text-md-end d-block">Radio {{ strtoupper($arLocale) }} - {{ \Illuminate\Support\Str::headline($radioKey) }}</label>
                                                <input type="text" class="form-control" dir="rtl"
                                                    name="contact_forms[{{ $key }}][{{ $arLocale }}][radio][{{ $radioKey }}]"
                                                    value="{{ old("contact_forms.$key.$arLocale.radio.$radioKey", $sectionAr['radio'][$radioKey] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Radio {{ strtoupper($enLocale) }} - {{ \Illuminate\Support\Str::headline($radioKey) }}</label>
                                                <input type="text" class="form-control"
                                                    name="contact_forms[{{ $key }}][{{ $enLocale }}][radio][{{ $radioKey }}]"
                                                    value="{{ old("contact_forms.$key.$enLocale.radio.$radioKey", $sectionEn['radio'][$radioKey] ?? '') }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (!empty($optionKeys))
                                    @foreach ($optionKeys as $optionKey)
                                        <div class="row g-3 flex-md-row-reverse">
                                            <div class="col-md-6 text-md-end">
                                                <label class="form-label text-md-end d-block">Options {{ strtoupper($arLocale) }} - {{ \Illuminate\Support\Str::headline($optionKey) }}</label>
                                                <textarea class="form-control" rows="3" dir="rtl" name="contact_forms[{{ $key }}][{{ $arLocale }}][options][{{ $optionKey }}]">{{ old("contact_forms.$key.$arLocale.options.$optionKey", is_array($sectionAr['options'][$optionKey] ?? null) ? implode("\n", $sectionAr['options'][$optionKey]) : ($sectionAr['options'][$optionKey] ?? '')) }}</textarea>
                                                <small class="text-muted">Enter each option on a separate line.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Options {{ strtoupper($enLocale) }} - {{ \Illuminate\Support\Str::headline($optionKey) }}</label>
                                                <textarea class="form-control" rows="3" name="contact_forms[{{ $key }}][{{ $enLocale }}][options][{{ $optionKey }}]">{{ old("contact_forms.$key.$enLocale.options.$optionKey", is_array($sectionEn['options'][$optionKey] ?? null) ? implode("\n", $sectionEn['options'][$optionKey]) : ($sectionEn['options'][$optionKey] ?? '')) }}</textarea>
                                                <small class="text-muted">Enter each option on a separate line.</small>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
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
