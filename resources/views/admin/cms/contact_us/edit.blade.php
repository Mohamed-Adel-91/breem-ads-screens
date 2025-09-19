@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')

                <form method="POST" action="{{ route('admin.cms.contact.update', ['lang' => app()->getLocale()]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @php
                        $banner = $sections->get('second_banner');
                        $bannerData = $banner?->section_data ?? [];
                        $contact = $sections->get('contact_us');
                        $contactData = $contact?->section_data ?? [];
                        $map = $sections->get('map');
                        $mapData = $map?->section_data ?? [];
                        $bottom = $sections->get('bottom_banner');
                        $bottomData = $bottom?->section_data ?? [];

                        $ads = $sections->get('contact_form_ads');
                        $adsData = $ads?->section_data ?? [];
                        $screens = $sections->get('contact_form_screens');
                        $screensData = $screens?->section_data ?? [];
                        $create = $sections->get('contact_form_create');
                        $createData = $create?->section_data ?? [];
                        $faq = $sections->get('contact_form_faq');
                        $faqData = $faq?->section_data ?? [];

                        $toJson = function ($value) {
                            if (empty($value)) {
                                return '';
                            }
                            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        };
                    @endphp

                    {{-- Banner --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Banner</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'banner_image',
                                    'inputId' => 'banner_image',
                                    'label' => 'Banner Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($bannerData, 'en.image_path'),
                                    'previewPath' => data_get($bannerData, 'en.image_path'),
                                ])
                            </div>
                        </div>
                    </div>

                    {{-- Contact Intro --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Intro Content</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="contact_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="contact_title_ar" name="contact_title_ar"
                                        value="{{ old('contact_title_ar', data_get($contactData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="contact_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="contact_title_en" name="contact_title_en"
                                        value="{{ old('contact_title_en', data_get($contactData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="contact_subtitle_ar">Subtitle (AR)</label>
                                    <textarea class="form-control" id="contact_subtitle_ar" name="contact_subtitle_ar" rows="2">{{ old('contact_subtitle_ar', data_get($contactData, 'ar.subtitle')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="contact_subtitle_en">Subtitle (EN)</label>
                                    <textarea class="form-control" id="contact_subtitle_en" name="contact_subtitle_en" rows="2">{{ old('contact_subtitle_en', data_get($contactData, 'en.subtitle')) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Map Section --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Map & Contact Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'map_background',
                                    'inputId' => 'map_background',
                                    'label' => 'Background Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($mapData, 'en.background_image_path'),
                                    'previewPath' => data_get($mapData, 'en.background_image_path'),
                                ])
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="map_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="map_title_ar" name="map_title_ar"
                                        value="{{ old('map_title_ar', data_get($mapData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="map_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="map_title_en" name="map_title_en"
                                        value="{{ old('map_title_en', data_get($mapData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="map_address_ar">Address (AR)</label>
                                    <textarea class="form-control" id="map_address_ar" name="map_address_ar" rows="3">{{ old('map_address_ar', data_get($mapData, 'ar.address')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="map_address_en">Address (EN)</label>
                                    <textarea class="form-control" id="map_address_en" name="map_address_en" rows="3">{{ old('map_address_en', data_get($mapData, 'en.address')) }}</textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="map_phone_label_ar">Phone Label (AR)</label>
                                    <input type="text" class="form-control" id="map_phone_label_ar" name="map_phone_label_ar"
                                        value="{{ old('map_phone_label_ar', data_get($mapData, 'ar.phone_label')) }}">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="map_phone_label_en">Phone Label (EN)</label>
                                    <input type="text" class="form-control" id="map_phone_label_en" name="map_phone_label_en"
                                        value="{{ old('map_phone_label_en', data_get($mapData, 'en.phone_label')) }}">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="map_whatsapp_label_ar">WhatsApp Label (AR)</label>
                                    <input type="text" class="form-control" id="map_whatsapp_label_ar"
                                        name="map_whatsapp_label_ar"
                                        value="{{ old('map_whatsapp_label_ar', data_get($mapData, 'ar.whatsapp_label')) }}">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="map_whatsapp_label_en">WhatsApp Label (EN)</label>
                                    <input type="text" class="form-control" id="map_whatsapp_label_en"
                                        name="map_whatsapp_label_en"
                                        value="{{ old('map_whatsapp_label_en', data_get($mapData, 'en.whatsapp_label')) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bottom Banner --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Bottom Banner</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'bottom_banner_image',
                                    'inputId' => 'bottom_banner_image',
                                    'label' => 'Banner Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($bottomData, 'en.image_path'),
                                    'previewPath' => data_get($bottomData, 'en.image_path'),
                                ])
                            </div>
                        </div>
                    </div>

                    {{-- Ads Form --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Ads Subscription Form</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'ads_card_image1',
                                    'inputId' => 'ads_card_image1',
                                    'label' => 'Card Image 1',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($adsData, 'en.card_image1'),
                                    'previewPath' => data_get($adsData, 'en.card_image1'),
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'ads_card_image2',
                                    'inputId' => 'ads_card_image2',
                                    'label' => 'Card Image 2',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($adsData, 'en.card_image2'),
                                    'previewPath' => data_get($adsData, 'en.card_image2'),
                                ])
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="ads_card_text_ar">Card Text (AR)</label>
                                    <textarea class="form-control" id="ads_card_text_ar" name="ads_card_text_ar" rows="2">{{ old('ads_card_text_ar', data_get($adsData, 'ar.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_card_text_en">Card Text (EN)</label>
                                    <textarea class="form-control" id="ads_card_text_en" name="ads_card_text_en" rows="2">{{ old('ads_card_text_en', data_get($adsData, 'en.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="ads_modal_title_ar">Modal Title (AR)</label>
                                    <input type="text" class="form-control" id="ads_modal_title_ar"
                                        name="ads_modal_title_ar"
                                        value="{{ old('ads_modal_title_ar', data_get($adsData, 'ar.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="ads_modal_title_en">Modal Title (EN)</label>
                                    <input type="text" class="form-control" id="ads_modal_title_en"
                                        name="ads_modal_title_en"
                                        value="{{ old('ads_modal_title_en', data_get($adsData, 'en.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="ads_submit_text_ar">Submit Text (AR)</label>
                                    <input type="text" class="form-control" id="ads_submit_text_ar"
                                        name="ads_submit_text_ar"
                                        value="{{ old('ads_submit_text_ar', data_get($adsData, 'ar.submit_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="ads_submit_text_en">Submit Text (EN)</label>
                                    <input type="text" class="form-control" id="ads_submit_text_en"
                                        name="ads_submit_text_en"
                                        value="{{ old('ads_submit_text_en', data_get($adsData, 'en.submit_text')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="ads_labels_ar">Labels (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_labels_ar" name="ads_labels_ar" rows="6">{{ old('ads_labels_ar', $toJson(data_get($adsData, 'ar.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_labels_en">Labels (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_labels_en" name="ads_labels_en" rows="6">{{ old('ads_labels_en', $toJson(data_get($adsData, 'en.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_radio_ar">Radio Options (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_radio_ar" name="ads_radio_ar" rows="4">{{ old('ads_radio_ar', $toJson(data_get($adsData, 'ar.radio'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_radio_en">Radio Options (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_radio_en" name="ads_radio_en" rows="4">{{ old('ads_radio_en', $toJson(data_get($adsData, 'en.radio'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_options_ar">Select Options (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_options_ar" name="ads_options_ar" rows="6">{{ old('ads_options_ar', $toJson(data_get($adsData, 'ar.options'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ads_options_en">Select Options (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="ads_options_en" name="ads_options_en" rows="6">{{ old('ads_options_en', $toJson(data_get($adsData, 'en.options'))) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Screens Form --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Screens Subscription Form</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'screens_card_image1',
                                    'inputId' => 'screens_card_image1',
                                    'label' => 'Card Image 1',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($screensData, 'en.card_image1'),
                                    'previewPath' => data_get($screensData, 'en.card_image1'),
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'screens_card_image2',
                                    'inputId' => 'screens_card_image2',
                                    'label' => 'Card Image 2',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($screensData, 'en.card_image2'),
                                    'previewPath' => data_get($screensData, 'en.card_image2'),
                                ])
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="screens_card_text_ar">Card Text (AR)</label>
                                    <textarea class="form-control" id="screens_card_text_ar" name="screens_card_text_ar" rows="2">{{ old('screens_card_text_ar', data_get($screensData, 'ar.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_card_text_en">Card Text (EN)</label>
                                    <textarea class="form-control" id="screens_card_text_en" name="screens_card_text_en" rows="2">{{ old('screens_card_text_en', data_get($screensData, 'en.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="screens_modal_title_ar">Modal Title (AR)</label>
                                    <input type="text" class="form-control" id="screens_modal_title_ar"
                                        name="screens_modal_title_ar"
                                        value="{{ old('screens_modal_title_ar', data_get($screensData, 'ar.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="screens_modal_title_en">Modal Title (EN)</label>
                                    <input type="text" class="form-control" id="screens_modal_title_en"
                                        name="screens_modal_title_en"
                                        value="{{ old('screens_modal_title_en', data_get($screensData, 'en.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="screens_submit_text_ar">Submit Text (AR)</label>
                                    <input type="text" class="form-control" id="screens_submit_text_ar"
                                        name="screens_submit_text_ar"
                                        value="{{ old('screens_submit_text_ar', data_get($screensData, 'ar.submit_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="screens_submit_text_en">Submit Text (EN)</label>
                                    <input type="text" class="form-control" id="screens_submit_text_en"
                                        name="screens_submit_text_en"
                                        value="{{ old('screens_submit_text_en', data_get($screensData, 'en.submit_text')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="screens_labels_ar">Labels (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_labels_ar" name="screens_labels_ar" rows="6">{{ old('screens_labels_ar', $toJson(data_get($screensData, 'ar.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_labels_en">Labels (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_labels_en" name="screens_labels_en" rows="6">{{ old('screens_labels_en', $toJson(data_get($screensData, 'en.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_radio_ar">Radio Options (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_radio_ar" name="screens_radio_ar" rows="4">{{ old('screens_radio_ar', $toJson(data_get($screensData, 'ar.radio'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_radio_en">Radio Options (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_radio_en" name="screens_radio_en" rows="4">{{ old('screens_radio_en', $toJson(data_get($screensData, 'en.radio'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_options_ar">Select Options (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_options_ar" name="screens_options_ar" rows="6">{{ old('screens_options_ar', $toJson(data_get($screensData, 'ar.options'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="screens_options_en">Select Options (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="screens_options_en" name="screens_options_en" rows="6">{{ old('screens_options_en', $toJson(data_get($screensData, 'en.options'))) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Create Form --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Create Ad Form</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'create_card_image1',
                                    'inputId' => 'create_card_image1',
                                    'label' => 'Card Image 1',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($createData, 'en.card_image1'),
                                    'previewPath' => data_get($createData, 'en.card_image1'),
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'create_card_image2',
                                    'inputId' => 'create_card_image2',
                                    'label' => 'Card Image 2',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($createData, 'en.card_image2'),
                                    'previewPath' => data_get($createData, 'en.card_image2'),
                                ])
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="create_card_text_ar">Card Text (AR)</label>
                                    <textarea class="form-control" id="create_card_text_ar" name="create_card_text_ar" rows="2">{{ old('create_card_text_ar', data_get($createData, 'ar.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="create_card_text_en">Card Text (EN)</label>
                                    <textarea class="form-control" id="create_card_text_en" name="create_card_text_en" rows="2">{{ old('create_card_text_en', data_get($createData, 'en.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="create_modal_title_ar">Modal Title (AR)</label>
                                    <input type="text" class="form-control" id="create_modal_title_ar"
                                        name="create_modal_title_ar"
                                        value="{{ old('create_modal_title_ar', data_get($createData, 'ar.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="create_modal_title_en">Modal Title (EN)</label>
                                    <input type="text" class="form-control" id="create_modal_title_en"
                                        name="create_modal_title_en"
                                        value="{{ old('create_modal_title_en', data_get($createData, 'en.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="create_submit_text_ar">Submit Text (AR)</label>
                                    <input type="text" class="form-control" id="create_submit_text_ar"
                                        name="create_submit_text_ar"
                                        value="{{ old('create_submit_text_ar', data_get($createData, 'ar.submit_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="create_submit_text_en">Submit Text (EN)</label>
                                    <input type="text" class="form-control" id="create_submit_text_en"
                                        name="create_submit_text_en"
                                        value="{{ old('create_submit_text_en', data_get($createData, 'en.submit_text')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="create_labels_ar">Labels (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="create_labels_ar" name="create_labels_ar" rows="6">{{ old('create_labels_ar', $toJson(data_get($createData, 'ar.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="create_labels_en">Labels (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="create_labels_en" name="create_labels_en" rows="6">{{ old('create_labels_en', $toJson(data_get($createData, 'en.labels'))) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- FAQ Form --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">FAQ Form</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'faq_card_image1',
                                    'inputId' => 'faq_card_image1',
                                    'label' => 'Card Image 1',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($faqData, 'en.card_image1'),
                                    'previewPath' => data_get($faqData, 'en.card_image1'),
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'faq_card_image2',
                                    'inputId' => 'faq_card_image2',
                                    'label' => 'Card Image 2',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($faqData, 'en.card_image2'),
                                    'previewPath' => data_get($faqData, 'en.card_image2'),
                                ])
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="faq_card_text_ar">Card Text (AR)</label>
                                    <textarea class="form-control" id="faq_card_text_ar" name="faq_card_text_ar" rows="2">{{ old('faq_card_text_ar', data_get($faqData, 'ar.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="faq_card_text_en">Card Text (EN)</label>
                                    <textarea class="form-control" id="faq_card_text_en" name="faq_card_text_en" rows="2">{{ old('faq_card_text_en', data_get($faqData, 'en.card_text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="faq_modal_title_ar">Modal Title (AR)</label>
                                    <input type="text" class="form-control" id="faq_modal_title_ar"
                                        name="faq_modal_title_ar"
                                        value="{{ old('faq_modal_title_ar', data_get($faqData, 'ar.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="faq_modal_title_en">Modal Title (EN)</label>
                                    <input type="text" class="form-control" id="faq_modal_title_en"
                                        name="faq_modal_title_en"
                                        value="{{ old('faq_modal_title_en', data_get($faqData, 'en.modal_title')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="faq_submit_text_ar">Submit Text (AR)</label>
                                    <input type="text" class="form-control" id="faq_submit_text_ar"
                                        name="faq_submit_text_ar"
                                        value="{{ old('faq_submit_text_ar', data_get($faqData, 'ar.submit_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="faq_submit_text_en">Submit Text (EN)</label>
                                    <input type="text" class="form-control" id="faq_submit_text_en"
                                        name="faq_submit_text_en"
                                        value="{{ old('faq_submit_text_en', data_get($faqData, 'en.submit_text')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="faq_labels_ar">Labels (AR)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="faq_labels_ar" name="faq_labels_ar" rows="6">{{ old('faq_labels_ar', $toJson(data_get($faqData, 'ar.labels'))) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="faq_labels_en">Labels (EN)</label>
                                    <small class="text-muted d-block mb-1">JSON format</small>
                                    <textarea class="form-control" id="faq_labels_en" name="faq_labels_en" rows="6">{{ old('faq_labels_en', $toJson(data_get($faqData, 'en.labels'))) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
