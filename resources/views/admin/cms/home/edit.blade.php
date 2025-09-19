@extends('admin.layouts.master')
@section('content')
    @php
        $primaryLocale = config('app.locale');
        $arabicLocale = 'ar';
        $englishLocale = 'en';
    @endphp
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.cms.home.update', ['lang' => app()->getLocale()]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('admin.sidebar.home_page') }} - Banner</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => 'Video',
                                'name' => 'banner[video]',
                                'inputId' => 'home_banner_video',
                                'acceptedTypes' => 'video/mp4',
                                'previewPath' => media_path($bannerData[$primaryLocale]['video_path'] ?? ''),
                            ])
                            <div class="row g-3 mt-0">
                                @foreach (['autoplay', 'loop', 'muted', 'controls', 'playsinline'] as $flag)
                                    <div class="col-md-4">
                                        <div class="form-check mt-3">
                                            <input type="hidden" name="banner[{{ $flag }}]" value="0">
                                            <input type="checkbox" class="form-check-input" id="banner_{{ $flag }}"
                                                name="banner[{{ $flag }}]"
                                                value="1" @checked(old("banner.$flag", $bannerData[$primaryLocale][$flag] ?? false))>
                                            <label class="form-check-label" for="banner_{{ $flag }}">{{ ucfirst($flag) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="partners-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Partners Slider</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-partner-item">{{ __('admin.buttons.new') }}</button>
                        </div>
                        <div class="card-body" id="partner-items-container">
                            @php $partnerIndex = 0; @endphp
                            @foreach ($partners?->items ?? [] as $item)
                                @php
                                    $itemData = $partnerItemData[$item->id] ?? [];
                                    $currentPath = $itemData[$primaryLocale]['image_path'] ?? '';
                                @endphp
                                <div class="card mb-3 partner-item" data-index="{{ $partnerIndex }}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>#{{ $item->id }}</strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-partner-item">&times;</button>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="partners[items][{{ $partnerIndex }}][id]" value="{{ $item->id }}">
                                        <input type="hidden" name="partners[items][{{ $partnerIndex }}][existing_image]" value="{{ $currentPath }}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Order</label>
                                                <input type="number" name="partners[items][{{ $partnerIndex }}][order]" class="form-control"
                                                    value="{{ old("partners.items.$partnerIndex.order", $item->order) }}">
                                            </div>
                                            <div class="col-md-9">
                                                @include('admin.layouts.components.media-upload', [
                                                    'label' => 'Slide Image',
                                                    'name' => "partners[items][$partnerIndex][image]",
                                                    'inputId' => "partners_item_{$partnerIndex}_image",
                                                    'previewPath' => media_path($currentPath),
                                                ])
                                            </div>
                                            <div class="col-12">
                                                <div class="row g-3 flex-md-row-reverse">
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Alt ({{ strtoupper($arabicLocale) }})</label>
                                                        <input type="text" class="form-control" dir="rtl"
                                                            name="partners[items][{{ $partnerIndex }}][alt][{{ $arabicLocale }}]"
                                                            value="{{ old("partners.items.$partnerIndex.alt.$arabicLocale", $itemData[$arabicLocale]['alt'] ?? '') }}">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Alt ({{ strtoupper($englishLocale) }})</label>
                                                        <input type="text" class="form-control"
                                                            name="partners[items][{{ $partnerIndex }}][alt][{{ $englishLocale }}]"
                                                            value="{{ old("partners.items.$partnerIndex.alt.$englishLocale", $itemData[$englishLocale]['alt'] ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php $partnerIndex++; @endphp
                            @endforeach
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">About Section</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Title ({{ strtoupper($arabicLocale) }})</label>
                                            <input type="text" class="form-control" dir="rtl"
                                                name="about[{{ $arabicLocale }}][title]"
                                                value="{{ old("about.$arabicLocale.title", $aboutData[$arabicLocale]['title'] ?? '') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Title ({{ strtoupper($englishLocale) }})</label>
                                            <input type="text" class="form-control" name="about[{{ $englishLocale }}][title]"
                                                value="{{ old("about.$englishLocale.title", $aboutData[$englishLocale]['title'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Description ({{ strtoupper($arabicLocale) }})</label>
                                            <textarea class="form-control" rows="3" dir="rtl"
                                                name="about[{{ $arabicLocale }}][desc]">{{ old("about.$arabicLocale.desc", $aboutData[$arabicLocale]['desc'] ?? '') }}</textarea>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Description ({{ strtoupper($englishLocale) }})</label>
                                            <textarea class="form-control" rows="3" name="about[{{ $englishLocale }}][desc]">{{ old("about.$englishLocale.desc", $aboutData[$englishLocale]['desc'] ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Read more text ({{ strtoupper($arabicLocale) }})</label>
                                            <input type="text" class="form-control" dir="rtl"
                                                name="about[{{ $arabicLocale }}][readmore_text]"
                                                value="{{ old("about.$arabicLocale.readmore_text", $aboutData[$arabicLocale]['readmore_text'] ?? '') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Read more text ({{ strtoupper($englishLocale) }})</label>
                                            <input type="text" class="form-control"
                                                name="about[{{ $englishLocale }}][readmore_text]"
                                                value="{{ old("about.$englishLocale.readmore_text", $aboutData[$englishLocale]['readmore_text'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Read more link ({{ strtoupper($arabicLocale) }})</label>
                                            <input type="text" class="form-control" dir="rtl"
                                                name="about[{{ $arabicLocale }}][readmore_link]"
                                                value="{{ old("about.$arabicLocale.readmore_link", $aboutData[$arabicLocale]['readmore_link'] ?? '') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Read more link ({{ strtoupper($englishLocale) }})</label>
                                            <input type="text" class="form-control"
                                                name="about[{{ $englishLocale }}][readmore_link]"
                                                value="{{ old("about.$englishLocale.readmore_link", $aboutData[$englishLocale]['readmore_link'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="stats-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Impact Metrics</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-stat-item">{{ __('admin.buttons.new') }}</button>
                        </div>
                        <div class="card-body" id="stats-items-container">
                            @php $statsIndex = 0; @endphp
                            @foreach ($stats?->items ?? [] as $item)
                                @php
                                    $itemData = $statsItemData[$item->id] ?? [];
                                    $currentIcon = $itemData[$primaryLocale]['icon_path'] ?? '';
                                @endphp
                                <div class="card mb-3 stat-item" data-index="{{ $statsIndex }}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>#{{ $item->id }}</strong>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-stat-item">&times;</button>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="stats[items][{{ $statsIndex }}][id]" value="{{ $item->id }}">
                                        <input type="hidden" name="stats[items][{{ $statsIndex }}][existing_icon]" value="{{ $currentIcon }}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Order</label>
                                                <input type="number" name="stats[items][{{ $statsIndex }}][order]" class="form-control"
                                                    value="{{ old("stats.items.$statsIndex.order", $item->order) }}">
                                            </div>
                                            <div class="col-md-9">
                                                @include('admin.layouts.components.media-upload', [
                                                    'label' => 'Icon',
                                                    'name' => "stats[items][$statsIndex][icon]",
                                                    'inputId' => "stats_item_{$statsIndex}_icon",
                                                    'previewPath' => media_path($currentIcon),
                                                ])
                                            </div>
                                            <div class="col-12">
                                                <div class="row g-3 flex-md-row-reverse">
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Number ({{ strtoupper($arabicLocale) }})</label>
                                                        <input type="text" class="form-control" dir="rtl"
                                                            name="stats[items][{{ $statsIndex }}][number][{{ $arabicLocale }}]"
                                                            value="{{ old("stats.items.$statsIndex.number.$arabicLocale", $itemData[$arabicLocale]['number'] ?? '') }}">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Number ({{ strtoupper($englishLocale) }})</label>
                                                        <input type="text" class="form-control"
                                                            name="stats[items][{{ $statsIndex }}][number][{{ $englishLocale }}]"
                                                            value="{{ old("stats.items.$statsIndex.number.$englishLocale", $itemData[$englishLocale]['number'] ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row g-3 flex-md-row-reverse">
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Label ({{ strtoupper($arabicLocale) }})</label>
                                                        <input type="text" class="form-control" dir="rtl"
                                                            name="stats[items][{{ $statsIndex }}][label][{{ $arabicLocale }}]"
                                                            value="{{ old("stats.items.$statsIndex.label.$arabicLocale", $itemData[$arabicLocale]['label'] ?? '') }}">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">Label ({{ strtoupper($englishLocale) }})</label>
                                                        <input type="text" class="form-control"
                                                            name="stats[items][{{ $statsIndex }}][label][{{ $englishLocale }}]"
                                                            value="{{ old("stats.items.$statsIndex.label.$englishLocale", $itemData[$englishLocale]['label'] ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php $statsIndex++; @endphp
                            @endforeach
                        </div>
                    </div>

                    <div class="card mb-4" id="where-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Where To Find Us</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-where-item">{{ __('admin.buttons.new') }}</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Title ({{ strtoupper($arabicLocale) }})</label>
                                            <input type="text" class="form-control" dir="rtl"
                                                name="where_us[title][{{ $arabicLocale }}]"
                                                value="{{ old("where_us.title.$arabicLocale", $whereData[$arabicLocale]['title'] ?? '') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Title ({{ strtoupper($englishLocale) }})</label>
                                            <input type="text" class="form-control" name="where_us[title][{{ $englishLocale }}]"
                                                value="{{ old("where_us.title.$englishLocale", $whereData[$englishLocale]['title'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row g-3 flex-md-row-reverse">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Brochure text ({{ strtoupper($arabicLocale) }})</label>
                                            <input type="text" class="form-control" dir="rtl"
                                                name="where_us[brochure_text][{{ $arabicLocale }}]"
                                                value="{{ old("where_us.brochure_text.$arabicLocale", $whereData[$arabicLocale]['brochure']['text'] ?? '') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Brochure text ({{ strtoupper($englishLocale) }})</label>
                                            <input type="text" class="form-control"
                                                name="where_us[brochure_text][{{ $englishLocale }}]"
                                                value="{{ old("where_us.brochure_text.$englishLocale", $whereData[$englishLocale]['brochure']['text'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @include('admin.layouts.components.media-upload', [
                                        'label' => 'Brochure Icon',
                                        'name' => 'where_us[brochure_icon]',
                                        'inputId' => 'where_brochure_icon',
                                        'previewPath' => media_path($whereData[$primaryLocale]['brochure']['icon_path'] ?? ''),
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('admin.layouts.components.media-upload', [
                                        'label' => 'Brochure File (PDF)',
                                        'name' => 'where_us[brochure_file]',
                                        'inputId' => 'where_brochure_file',
                                        'acceptedTypes' => 'application/pdf',
                                        'previewPath' => media_path($whereData[$primaryLocale]['brochure']['brochure_path'] ?? ''),
                                    ])
                                    <label class="form-label mt-2">Brochure external link</label>
                                    <input type="text" class="form-control" name="where_us[brochure_link]"
                                        value="{{ old('where_us.brochure_link', $whereData[$primaryLocale]['brochure']['brochure_path'] ?? '') }}">
                                </div>
                            </div>
                            <hr>
                            <div id="where-items-container">
                                @php $whereIndex = 0; @endphp
                                @foreach ($whereUs?->items ?? [] as $item)
                                    @php
                                        $itemData = $whereItemsData[$item->id] ?? [];
                                        $currentImage = $itemData[$primaryLocale]['image_path'] ?? '';
                                    @endphp
                                    <div class="card mb-3 where-item" data-index="{{ $whereIndex }}">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <strong>#{{ $item->id }}</strong>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-where-item">&times;</button>
                                        </div>
                                        <div class="card-body">
                                            <input type="hidden" name="where_us[items][{{ $whereIndex }}][id]" value="{{ $item->id }}">
                                            <input type="hidden" name="where_us[items][{{ $whereIndex }}][existing_image]" value="{{ $currentImage }}">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Order</label>
                                                    <input type="number" class="form-control" name="where_us[items][{{ $whereIndex }}][order]"
                                                        value="{{ old("where_us.items.$whereIndex.order", $item->order) }}">
                                                </div>
                                                <div class="col-md-9">
                                                    @include('admin.layouts.components.media-upload', [
                                                        'label' => 'Image',
                                                        'name' => "where_us[items][$whereIndex][image]",
                                                        'inputId' => "where_item_{$whereIndex}_image",
                                                        'previewPath' => media_path($currentImage),
                                                    ])
                                                </div>
                                                <div class="col-12">
                                                    <div class="row g-3 flex-md-row-reverse">
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label">Overlay text ({{ strtoupper($arabicLocale) }})</label>
                                                            <input type="text" class="form-control" dir="rtl"
                                                                name="where_us[items][{{ $whereIndex }}][overlay][{{ $arabicLocale }}]"
                                                                value="{{ old("where_us.items.$whereIndex.overlay.$arabicLocale", $itemData[$arabicLocale]['overlay_text'] ?? '') }}">
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label">Overlay text ({{ strtoupper($englishLocale) }})</label>
                                                            <input type="text" class="form-control"
                                                                name="where_us[items][{{ $whereIndex }}][overlay][{{ $englishLocale }}]"
                                                                value="{{ old("where_us.items.$whereIndex.overlay.$englishLocale", $itemData[$englishLocale]['overlay_text'] ?? '') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @php $whereIndex++; @endphp
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">CTA Section</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    @include('admin.layouts.components.media-upload', [
                                        'label' => 'Main Image',
                                        'name' => 'cta[image]',
                                        'inputId' => 'cta_image',
                                        'previewPath' => media_path($ctaData[$primaryLocale]['image_path'] ?? ''),
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('admin.layouts.components.media-upload', [
                                        'label' => 'Overlay Image',
                                        'name' => 'cta[overlay_image]',
                                        'inputId' => 'cta_overlay_image',
                                        'previewPath' => media_path($ctaData[$primaryLocale]['overlay_image_path'] ?? ''),
                                    ])
                                </div>
                                @foreach ($locales as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="cta[{{ $locale }}][title]"
                                            value="{{ old("cta.$locale.title", $ctaData[$locale]['title'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Text ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control" rows="3" name="cta[{{ $locale }}][text]">{{ old("cta.$locale.text", $ctaData[$locale]['text'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Link Text ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="cta[{{ $locale }}][link_text]"
                                            value="{{ old("cta.$locale.link_text", $ctaData[$locale]['link_text'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Link URL ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="cta[{{ $locale }}][link_url]"
                                            value="{{ old("cta.$locale.link_url", $ctaData[$locale]['link_url'] ?? '') }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('custom-js-scripts')
    <template id="partner-item-template">
        <div class="card mb-3 partner-item" data-index="__INDEX__">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>New</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-partner-item">&times;</button>
            </div>
            <div class="card-body">
                <input type="hidden" name="partners[items][__INDEX__][existing_image]">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="partners[items][__INDEX__][order]" class="form-control">
                    </div>
                    <div class="col-md-9">
                        @include('admin.layouts.components.media-upload', [
                            'label' => 'Slide Image',
                            'name' => 'partners[items][__INDEX__][image]',
                            'inputId' => 'partners_item___INDEX___image',
                        ])
                    </div>
                    <div class="col-12">
                        <div class="row g-3 flex-md-row-reverse">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Alt ({{ strtoupper($arabicLocale) }})</label>
                                <input type="text" class="form-control" dir="rtl"
                                    name="partners[items][__INDEX__][alt][{{ $arabicLocale }}]">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Alt ({{ strtoupper($englishLocale) }})</label>
                                <input type="text" class="form-control"
                                    name="partners[items][__INDEX__][alt][{{ $englishLocale }}]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template id="stat-item-template">
        <div class="card mb-3 stat-item" data-index="__INDEX__">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>New</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-stat-item">&times;</button>
            </div>
            <div class="card-body">
                <input type="hidden" name="stats[items][__INDEX__][existing_icon]">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="stats[items][__INDEX__][order]" class="form-control">
                    </div>
                    <div class="col-md-9">
                        @include('admin.layouts.components.media-upload', [
                            'label' => 'Icon',
                            'name' => 'stats[items][__INDEX__][icon]',
                            'inputId' => 'stats_item___INDEX___icon',
                        ])
                    </div>
                    <div class="col-12">
                        <div class="row g-3 flex-md-row-reverse">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Number ({{ strtoupper($arabicLocale) }})</label>
                                <input type="text" class="form-control" dir="rtl"
                                    name="stats[items][__INDEX__][number][{{ $arabicLocale }}]">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Number ({{ strtoupper($englishLocale) }})</label>
                                <input type="text" class="form-control"
                                    name="stats[items][__INDEX__][number][{{ $englishLocale }}]">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row g-3 flex-md-row-reverse">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Label ({{ strtoupper($arabicLocale) }})</label>
                                <input type="text" class="form-control" dir="rtl"
                                    name="stats[items][__INDEX__][label][{{ $arabicLocale }}]">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Label ({{ strtoupper($englishLocale) }})</label>
                                <input type="text" class="form-control"
                                    name="stats[items][__INDEX__][label][{{ $englishLocale }}]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template id="where-item-template">
        <div class="card mb-3 where-item" data-index="__INDEX__">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>New</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-where-item">&times;</button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="where_us[items][__INDEX__][order]" class="form-control">
                    </div>
                    <div class="col-md-9">
                        @include('admin.layouts.components.media-upload', [
                            'label' => 'Image',
                            'name' => 'where_us[items][__INDEX__][image]',
                            'inputId' => 'where_item___INDEX___image',
                        ])
                    </div>
                    <div class="col-12">
                        <div class="row g-3 flex-md-row-reverse">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Overlay text ({{ strtoupper($arabicLocale) }})</label>
                                <input type="text" class="form-control" dir="rtl"
                                    name="where_us[items][__INDEX__][overlay][{{ $arabicLocale }}]">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Overlay text ({{ strtoupper($englishLocale) }})</label>
                                <input type="text" class="form-control"
                                    name="where_us[items][__INDEX__][overlay][{{ $englishLocale }}]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <script>
        function initializeDynamicUpload(container) {
            container.querySelectorAll('[data-media-upload]').forEach(function (input) {
                window.registerMediaUpload(input.getAttribute('id'));
            });
        }

        function addItem(buttonId, templateId, containerId) {
            const button = document.getElementById(buttonId);
            const template = document.getElementById(templateId);
            const container = document.getElementById(containerId);

            if (!button || !template || !container) {
                return;
            }

            button.addEventListener('click', function () {
                const index = Date.now();
                let html = template.innerHTML.replace(/__INDEX__/g, index);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const element = wrapper.firstElementChild;
                container.appendChild(element);
                initializeDynamicUpload(element);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            addItem('add-partner-item', 'partner-item-template', 'partner-items-container');
            addItem('add-stat-item', 'stat-item-template', 'stats-items-container');
            addItem('add-where-item', 'where-item-template', 'where-items-container');

            document.getElementById('partner-items-container').addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-partner-item')) {
                    event.target.closest('.partner-item').remove();
                }
            });

            document.getElementById('stats-items-container').addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-stat-item')) {
                    event.target.closest('.stat-item').remove();
                }
            });

            document.getElementById('where-items-container').addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-where-item')) {
                    event.target.closest('.where-item').remove();
                }
            });

            document.querySelectorAll('.partner-item, .stat-item, .where-item').forEach(initializeDynamicUpload);
        });
    </script>
@endpush
