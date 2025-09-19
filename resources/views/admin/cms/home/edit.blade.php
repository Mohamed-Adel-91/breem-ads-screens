@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')

                <form method="POST" action="{{ route('admin.cms.home.update', ['lang' => app()->getLocale()]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @php
                        $banner = $sections->get('banner');
                        $bannerData = $banner?->section_data ?? [];
                        $partners = $sections->get('partners');
                        $about = $sections->get('about');
                        $aboutData = $about?->section_data ?? [];
                        $stats = $sections->get('stats');
                        $where = $sections->get('where_us');
                        $whereData = $where?->section_data ?? [];
                        $cta = $sections->get('cta');
                        $ctaData = $cta?->section_data ?? [];
                        $brochurePreview = data_get($whereData, 'en.brochure.brochure_path');
                        if ($brochurePreview === '#') {
                            $brochurePreview = null;
                        }
                    @endphp

                    {{-- Banner --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Banner</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'banner_video',
                                    'inputId' => 'banner_video',
                                    'label' => 'Video (MP4)',
                                    'acceptedTypes' => 'video/mp4',
                                    'isVideo' => true,
                                    'oldFile' => data_get($bannerData, 'en.video_path'),
                                    'previewPath' => data_get($bannerData, 'en.video_path'),
                                ])
                            </div>
                            <div class="row mt-3">
                                @php
                                    $flags = [
                                        'autoplay' => 'Autoplay',
                                        'loop' => 'Loop',
                                        'muted' => 'Muted',
                                        'controls' => 'Show Controls',
                                        'playsinline' => 'Plays Inline',
                                    ];
                                @endphp
                                @foreach ($flags as $flagKey => $flagLabel)
                                    <div class="col-md-2 col-sm-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="banner_{{ $flagKey }}" name="banner_{{ $flagKey }}"
                                                {{ old('banner_' . $flagKey, data_get($bannerData, 'en.' . $flagKey)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="banner_{{ $flagKey }}">{{ $flagLabel }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Partners Slider --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Partners Slider</h5>
                        </div>
                        <div class="card-body">
                            @if ($partners && $partners->items->count())
                                @foreach ($partners->items as $item)
                                    @php $itemData = $item->data ?? []; @endphp
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="mb-3">Slide #{{ $item->order }}</h6>
                                        <div class="row gutters">
                                            @include('admin.layouts.components.media-upload', [
                                                'name' => 'partners[' . $item->id . '][image]',
                                                'inputId' => 'partner_image_' . $item->id,
                                                'label' => 'Image',
                                                'oldFile' => data_get($itemData, 'en.image_path'),
                                                'previewPath' => data_get($itemData, 'en.image_path'),
                                                'acceptedTypes' => 'image/*',
                                            ])
                                            <div class="form-group col-md-2 col-sm-4">
                                                <label for="partner_order_{{ $item->id }}">Order</label>
                                                <input type="number" min="1" class="form-control"
                                                    id="partner_order_{{ $item->id }}"
                                                    name="partners[{{ $item->id }}][order]"
                                                    value="{{ old('partners.' . $item->id . '.order', $item->order) }}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="partner_alt_ar_{{ $item->id }}">Alt (AR)</label>
                                                <input type="text" class="form-control"
                                                    id="partner_alt_ar_{{ $item->id }}"
                                                    name="partners[{{ $item->id }}][alt_ar]"
                                                    value="{{ old('partners.' . $item->id . '.alt_ar', data_get($itemData, 'ar.alt')) }}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="partner_alt_en_{{ $item->id }}">Alt (EN)</label>
                                                <input type="text" class="form-control"
                                                    id="partner_alt_en_{{ $item->id }}"
                                                    name="partners[{{ $item->id }}][alt_en]"
                                                    value="{{ old('partners.' . $item->id . '.alt_en', data_get($itemData, 'en.alt')) }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No partners configured.</p>
                            @endif
                        </div>
                    </div>

                    {{-- About Section --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">About Section</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="about_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="about_title_ar" name="about_title_ar"
                                        value="{{ old('about_title_ar', data_get($aboutData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="about_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="about_title_en" name="about_title_en"
                                        value="{{ old('about_title_en', data_get($aboutData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="about_desc_ar">Description (AR)</label>
                                    <textarea class="form-control" id="about_desc_ar" name="about_desc_ar" rows="4">{{ old('about_desc_ar', data_get($aboutData, 'ar.desc')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="about_desc_en">Description (EN)</label>
                                    <textarea class="form-control" id="about_desc_en" name="about_desc_en" rows="4">{{ old('about_desc_en', data_get($aboutData, 'en.desc')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="about_readmore_text_ar">Read More Text (AR)</label>
                                    <input type="text" class="form-control" id="about_readmore_text_ar"
                                        name="about_readmore_text_ar"
                                        value="{{ old('about_readmore_text_ar', data_get($aboutData, 'ar.readmore_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="about_readmore_text_en">Read More Text (EN)</label>
                                    <input type="text" class="form-control" id="about_readmore_text_en"
                                        name="about_readmore_text_en"
                                        value="{{ old('about_readmore_text_en', data_get($aboutData, 'en.readmore_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="about_readmore_link">Read More Link</label>
                                    <input type="text" class="form-control" id="about_readmore_link"
                                        name="about_readmore_link"
                                        value="{{ old('about_readmore_link', data_get($aboutData, 'en.readmore_link')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Section --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Media Stats</h5>
                        </div>
                        <div class="card-body">
                            @if ($stats && $stats->items->count())
                                @foreach ($stats->items as $item)
                                    @php $itemData = $item->data ?? []; @endphp
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="mb-3">Item #{{ $item->order }}</h6>
                                        <div class="row gutters">
                                            @include('admin.layouts.components.media-upload', [
                                                'name' => 'stats[' . $item->id . '][icon]',
                                                'inputId' => 'stat_icon_' . $item->id,
                                                'label' => 'Icon',
                                                'oldFile' => data_get($itemData, 'en.icon_path'),
                                                'previewPath' => data_get($itemData, 'en.icon_path'),
                                                'acceptedTypes' => 'image/*',
                                            ])
                                            <div class="form-group col-md-3">
                                                <label for="stat_number_ar_{{ $item->id }}">Number (AR)</label>
                                                <input type="text" class="form-control" id="stat_number_ar_{{ $item->id }}"
                                                    name="stats[{{ $item->id }}][number_ar]"
                                                    value="{{ old('stats.' . $item->id . '.number_ar', data_get($itemData, 'ar.number')) }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="stat_number_en_{{ $item->id }}">Number (EN)</label>
                                                <input type="text" class="form-control" id="stat_number_en_{{ $item->id }}"
                                                    name="stats[{{ $item->id }}][number_en]"
                                                    value="{{ old('stats.' . $item->id . '.number_en', data_get($itemData, 'en.number')) }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="stat_label_ar_{{ $item->id }}">Label (AR)</label>
                                                <input type="text" class="form-control" id="stat_label_ar_{{ $item->id }}"
                                                    name="stats[{{ $item->id }}][label_ar]"
                                                    value="{{ old('stats.' . $item->id . '.label_ar', data_get($itemData, 'ar.label')) }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="stat_label_en_{{ $item->id }}">Label (EN)</label>
                                                <input type="text" class="form-control" id="stat_label_en_{{ $item->id }}"
                                                    name="stats[{{ $item->id }}][label_en]"
                                                    value="{{ old('stats.' . $item->id . '.label_en', data_get($itemData, 'en.label')) }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No stats configured.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Where Section --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Where We Are</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="where_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="where_title_ar" name="where_title_ar"
                                        value="{{ old('where_title_ar', data_get($whereData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="where_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="where_title_en" name="where_title_en"
                                        value="{{ old('where_title_en', data_get($whereData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="where_brochure_text_ar">Brochure Text (AR)</label>
                                    <input type="text" class="form-control" id="where_brochure_text_ar"
                                        name="where_brochure_text_ar"
                                        value="{{ old('where_brochure_text_ar', data_get($whereData, 'ar.brochure.text')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="where_brochure_text_en">Brochure Text (EN)</label>
                                    <input type="text" class="form-control" id="where_brochure_text_en"
                                        name="where_brochure_text_en"
                                        value="{{ old('where_brochure_text_en', data_get($whereData, 'en.brochure.text')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="where_brochure_link">Brochure Link</label>
                                    <input type="text" class="form-control" id="where_brochure_link" name="where_brochure_link"
                                        value="{{ old('where_brochure_link', data_get($whereData, 'en.brochure.brochure_path')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'where_brochure_icon',
                                    'inputId' => 'where_brochure_icon',
                                    'label' => 'Brochure Icon',
                                    'oldFile' => data_get($whereData, 'en.brochure.icon_path'),
                                    'previewPath' => data_get($whereData, 'en.brochure.icon_path'),
                                    'acceptedTypes' => 'image/*',
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'where_brochure_file',
                                    'inputId' => 'where_brochure_file',
                                    'label' => 'Brochure File (PDF / Image)',
                                    'acceptedTypes' => 'application/pdf,image/*',
                                    'oldFile' => $brochurePreview,
                                    'previewPath' => $brochurePreview,
                                ])
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3">Slides</h6>
                            @if ($where && $where->items->count())
                                @foreach ($where->items as $item)
                                    @php $itemData = $item->data ?? []; @endphp
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="mb-3">Slide #{{ $item->order }}</h6>
                                        <div class="row gutters">
                                            @include('admin.layouts.components.media-upload', [
                                                'name' => 'where_items[' . $item->id . '][image]',
                                                'inputId' => 'where_image_' . $item->id,
                                                'label' => 'Image',
                                                'acceptedTypes' => 'image/*',
                                                'oldFile' => data_get($itemData, 'en.image_path'),
                                                'previewPath' => data_get($itemData, 'en.image_path'),
                                            ])
                                            <div class="form-group col-md-3">
                                                <label for="where_overlay_ar_{{ $item->id }}">Overlay (AR)</label>
                                                <input type="text" class="form-control"
                                                    id="where_overlay_ar_{{ $item->id }}"
                                                    name="where_items[{{ $item->id }}][overlay_ar]"
                                                    value="{{ old('where_items.' . $item->id . '.overlay_ar', data_get($itemData, 'ar.overlay_text')) }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="where_overlay_en_{{ $item->id }}">Overlay (EN)</label>
                                                <input type="text" class="form-control"
                                                    id="where_overlay_en_{{ $item->id }}"
                                                    name="where_items[{{ $item->id }}][overlay_en]"
                                                    value="{{ old('where_items.' . $item->id . '.overlay_en', data_get($itemData, 'en.overlay_text')) }}">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="where_order_{{ $item->id }}">Order</label>
                                                <input type="number" min="1" class="form-control"
                                                    id="where_order_{{ $item->id }}"
                                                    name="where_items[{{ $item->id }}][order]"
                                                    value="{{ old('where_items.' . $item->id . '.order', $item->order) }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No slides configured.</p>
                            @endif
                        </div>
                    </div>

                    {{-- CTA Section --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Call To Action</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="cta_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="cta_title_ar" name="cta_title_ar"
                                        value="{{ old('cta_title_ar', data_get($ctaData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="cta_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="cta_title_en" name="cta_title_en"
                                        value="{{ old('cta_title_en', data_get($ctaData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="cta_text_ar">Text (AR)</label>
                                    <textarea class="form-control" id="cta_text_ar" name="cta_text_ar" rows="3">{{ old('cta_text_ar', data_get($ctaData, 'ar.text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="cta_text_en">Text (EN)</label>
                                    <textarea class="form-control" id="cta_text_en" name="cta_text_en" rows="3">{{ old('cta_text_en', data_get($ctaData, 'en.text')) }}</textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="cta_link_text_ar">Link Text (AR)</label>
                                    <input type="text" class="form-control" id="cta_link_text_ar"
                                        name="cta_link_text_ar"
                                        value="{{ old('cta_link_text_ar', data_get($ctaData, 'ar.link_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="cta_link_text_en">Link Text (EN)</label>
                                    <input type="text" class="form-control" id="cta_link_text_en"
                                        name="cta_link_text_en"
                                        value="{{ old('cta_link_text_en', data_get($ctaData, 'en.link_text')) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="cta_link_url">Link URL</label>
                                    <input type="text" class="form-control" id="cta_link_url" name="cta_link_url"
                                        value="{{ old('cta_link_url', data_get($ctaData, 'en.link_url')) }}">
                                </div>
                            </div>
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'cta_image',
                                    'inputId' => 'cta_image',
                                    'label' => 'Main Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($ctaData, 'en.image_path'),
                                    'previewPath' => data_get($ctaData, 'en.image_path'),
                                ])
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'cta_overlay_image',
                                    'inputId' => 'cta_overlay_image',
                                    'label' => 'Overlay Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($ctaData, 'en.overlay_image_path'),
                                    'previewPath' => data_get($ctaData, 'en.overlay_image_path'),
                                ])
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
