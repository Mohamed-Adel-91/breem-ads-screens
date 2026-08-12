@extends('admin.layouts.master')

@section('title', $page->name)

@section('content')
    @php
        // Media paths are stored once per section and read from the primary
        // locale, exactly as the controller writes them.
        $primaryLocale = config('app.locale');
        $ar = 'ar';
        $en = 'en';
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $page->name,
            'subtitle' => __('admin.cms.ui.home_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.website_cms')],
                ['label' => __('admin.sidebar.home_page')],
            ],
        ])

        <form method="POST"
              action="{{ route('admin.cms.home.update', ['lang' => app()->getLocale()]) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Banner ------------------------------------------------------ --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.home.sections.banner', 'Banner')"
                :section-type="$banner?->type"
                :section-id="$banner?->id"
                :is-active="$banner?->is_active">

                <div class="form-row">
                    <div class="col-12 col-lg-6">
                        <x-admin.file-uploader
                            name="banner[video]"
                            :label="__('admin.forms.video')"
                            input-id="home_banner_video"
                            kind="video"
                            accept="video/mp4"
                            accepted-types-hint="MP4"
                            max-size-hint="150 MB"
                            :preview-path="media_path($bannerData[$primaryLocale]['video_path'] ?? '')" />
                    </div>

                    <div class="col-12 col-lg-6">
                        <fieldset class="form-group mb-0">
                            <legend class="col-form-label pt-0 h6">
                                {{ \App\Support\Lang::t('admin.cms.home.sections.banner', 'Banner') }}
                            </legend>

                            <div class="form-row">
                                @foreach (['autoplay', 'loop', 'muted', 'controls', 'playsinline'] as $flag)
                                    <div class="col-12 col-sm-6">
                                        <div class="form-check mb-2">
                                            <input type="hidden" name="banner[{{ $flag }}]" value="0">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   id="banner_{{ $flag }}"
                                                   name="banner[{{ $flag }}]"
                                                   value="1"
                                                   @checked(old("banner.$flag", $bannerData[$primaryLocale][$flag] ?? false))>
                                            <label class="form-check-label" for="banner_{{ $flag }}">
                                                {{ \App\Support\Lang::t('admin.cms.home.flags.' . $flag, ucfirst($flag)) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                </div>
            </x-admin.cms-section-card>

            {{-- Partners slider --------------------------------------------- --}}
            @php $partnerItems = $partners?->items ?? []; @endphp

            <x-admin.cms-section-card
                id="partners-section"
                :title="\App\Support\Lang::t('admin.cms.home.sections.partners_slider', 'Partners Slider')"
                :section-type="$partners?->type"
                :section-id="$partners?->id"
                :is-active="$partners?->is_active">

                <x-slot:actions>
                    <x-admin.badge variant="light">
                        {{ __('admin.cms.ui.items_count', ['count' => count($partnerItems)]) }}
                    </x-admin.badge>
                    <x-admin.btn size="sm" icon="plus" data-repeater-add="partners">
                        {{ __('admin.cms.ui.add_item') }}
                    </x-admin.btn>
                </x-slot:actions>

                <div data-repeater-items="partners">
                    <p class="text-muted small" data-repeater-empty @if (count($partnerItems)) hidden @endif>
                        {{ __('admin.cms.ui.no_items_yet') }}
                    </p>

                    @foreach ($partnerItems as $partnerIndex => $item)
                        @php
                            $itemData = $partnerItemData[$item->id] ?? [];
                            $currentPath = $itemData[$primaryLocale]['image_path'] ?? '';
                            $base = "partners[items][$partnerIndex]";
                            $dot = "partners.items.$partnerIndex";
                        @endphp

                        <div class="card mb-3 bg-light" data-repeater-item>
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <span class="font-weight-bold">
                                    {{ __('admin.cms.ui.item_reference', ['id' => $item->id]) }}
                                </span>
                                @include('admin.cms.partials.repeater-remove')
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="{{ $base }}[id]" value="{{ $item->id }}">
                                <input type="hidden" name="{{ $base }}[existing_image]" value="{{ $currentPath }}">

                                <div class="form-row">
                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label for="partners_item_{{ $partnerIndex }}_order">{{ __('admin.forms.order') }}</label>
                                            <input type="number"
                                                   id="partners_item_{{ $partnerIndex }}_order"
                                                   name="{{ $base }}[order]"
                                                   class="form-control"
                                                   min="0"
                                                   value="{{ old("$dot.order", $item->order) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <x-admin.file-uploader
                                            :name="$base . '[image]'"
                                            :label="\App\Support\Lang::t('admin.cms.home.labels.slide_image', 'Slide Image')"
                                            :input-id="'partners_item_' . $partnerIndex . '_image'"
                                            kind="image"
                                            max-size-hint="10 MB"
                                            :preview-path="media_path($currentPath)" />
                                    </div>
                                </div>

                                <x-admin.translatable-field
                                    label-key="admin.cms.shared.alt_locale"
                                    :id-prefix="'partners_alt_' . $partnerIndex"
                                    :names="['en' => $base . '[alt][' . $en . ']', 'ar' => $base . '[alt][' . $ar . ']']"
                                    :values="[
                                        'en' => old($dot . '.alt.' . $en, $itemData[$en]['alt'] ?? ''),
                                        'ar' => old($dot . '.alt.' . $ar, $itemData[$ar]['alt'] ?? ''),
                                    ]" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="form-text text-muted">{{ __('admin.cms.ui.unsaved_items_hint') }}</small>
            </x-admin.cms-section-card>

            {{-- About -------------------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.home.sections.about', 'About Section')"
                :section-type="$about?->type"
                :section-id="$about?->id"
                :is-active="$about?->is_active">

                @foreach ([
                    'title' => ['key' => 'admin.cms.shared.title_locale', 'type' => 'text'],
                    'desc' => ['key' => 'admin.cms.shared.description_locale', 'type' => 'textarea'],
                    'readmore_text' => ['key' => 'admin.cms.shared.read_more_text_locale', 'type' => 'text'],
                    'readmore_link' => ['key' => 'admin.cms.shared.read_more_link_locale', 'type' => 'text'],
                ] as $field => $meta)
                    <x-admin.translatable-field
                        :type="$meta['type']"
                        :label-key="$meta['key']"
                        :id-prefix="'about_' . $field"
                        :names="['en' => 'about[' . $en . '][' . $field . ']', 'ar' => 'about[' . $ar . '][' . $field . ']']"
                        :values="[
                            'en' => old('about.' . $en . '.' . $field, $aboutData[$en][$field] ?? ''),
                            'ar' => old('about.' . $ar . '.' . $field, $aboutData[$ar][$field] ?? ''),
                        ]" />
                @endforeach
            </x-admin.cms-section-card>

            {{-- Impact metrics ---------------------------------------------- --}}
            @php $statsItems = $stats?->items ?? []; @endphp

            <x-admin.cms-section-card
                id="stats-section"
                :title="\App\Support\Lang::t('admin.cms.home.sections.impact_metrics', 'Impact Metrics')"
                :section-type="$stats?->type"
                :section-id="$stats?->id"
                :is-active="$stats?->is_active">

                <x-slot:actions>
                    <x-admin.badge variant="light">
                        {{ __('admin.cms.ui.items_count', ['count' => count($statsItems)]) }}
                    </x-admin.badge>
                    <x-admin.btn size="sm" icon="plus" data-repeater-add="stats">
                        {{ __('admin.cms.ui.add_item') }}
                    </x-admin.btn>
                </x-slot:actions>

                <div data-repeater-items="stats">
                    <p class="text-muted small" data-repeater-empty @if (count($statsItems)) hidden @endif>
                        {{ __('admin.cms.ui.no_items_yet') }}
                    </p>

                    @foreach ($statsItems as $statsIndex => $item)
                        @php
                            $itemData = $statsItemData[$item->id] ?? [];
                            $currentIcon = $itemData[$primaryLocale]['icon_path'] ?? '';
                            $base = "stats[items][$statsIndex]";
                            $dot = "stats.items.$statsIndex";
                        @endphp

                        <div class="card mb-3 bg-light" data-repeater-item>
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <span class="font-weight-bold">
                                    {{ __('admin.cms.ui.item_reference', ['id' => $item->id]) }}
                                </span>
                                @include('admin.cms.partials.repeater-remove')
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="{{ $base }}[id]" value="{{ $item->id }}">
                                <input type="hidden" name="{{ $base }}[existing_icon]" value="{{ $currentIcon }}">

                                <div class="form-row">
                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label for="stats_item_{{ $statsIndex }}_order">{{ __('admin.forms.order') }}</label>
                                            <input type="number"
                                                   id="stats_item_{{ $statsIndex }}_order"
                                                   name="{{ $base }}[order]"
                                                   class="form-control"
                                                   min="0"
                                                   value="{{ old("$dot.order", $item->order) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <x-admin.file-uploader
                                            :name="$base . '[icon]'"
                                            :label="__('admin.forms.icon')"
                                            :input-id="'stats_item_' . $statsIndex . '_icon'"
                                            kind="image"
                                            max-size-hint="5 MB"
                                            :preview-path="media_path($currentIcon)" />
                                    </div>
                                </div>

                                @foreach ([
                                    'number' => 'admin.cms.shared.number_locale',
                                    'label' => 'admin.cms.shared.label_locale',
                                ] as $field => $labelKey)
                                    <x-admin.translatable-field
                                        :label-key="$labelKey"
                                        :id-prefix="'stats_' . $field . '_' . $statsIndex"
                                        :names="['en' => $base . '[' . $field . '][' . $en . ']', 'ar' => $base . '[' . $field . '][' . $ar . ']']"
                                        :values="[
                                            'en' => old($dot . '.' . $field . '.' . $en, $itemData[$en][$field] ?? ''),
                                            'ar' => old($dot . '.' . $field . '.' . $ar, $itemData[$ar][$field] ?? ''),
                                        ]" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="form-text text-muted">{{ __('admin.cms.ui.unsaved_items_hint') }}</small>
            </x-admin.cms-section-card>

            {{-- Where to find us -------------------------------------------- --}}
            @php $whereItems = $whereUs?->items ?? []; @endphp

            <x-admin.cms-section-card
                id="where-section"
                :title="\App\Support\Lang::t('admin.cms.home.sections.locations', 'Where To Find Us')"
                :section-type="$whereUs?->type"
                :section-id="$whereUs?->id"
                :is-active="$whereUs?->is_active">

                <x-slot:actions>
                    <x-admin.badge variant="light">
                        {{ __('admin.cms.ui.items_count', ['count' => count($whereItems)]) }}
                    </x-admin.badge>
                    <x-admin.btn size="sm" icon="plus" data-repeater-add="where">
                        {{ __('admin.cms.ui.add_item') }}
                    </x-admin.btn>
                </x-slot:actions>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.title_locale"
                    id-prefix="where_title"
                    :names="['en' => 'where_us[title][' . $en . ']', 'ar' => 'where_us[title][' . $ar . ']']"
                    :values="[
                        'en' => old('where_us.title.' . $en, $whereData[$en]['title'] ?? ''),
                        'ar' => old('where_us.title.' . $ar, $whereData[$ar]['title'] ?? ''),
                    ]" />

                <x-admin.translatable-field
                    label-key="admin.cms.shared.brochure_text_locale"
                    id-prefix="where_brochure_text"
                    :names="['en' => 'where_us[brochure_text][' . $en . ']', 'ar' => 'where_us[brochure_text][' . $ar . ']']"
                    :values="[
                        'en' => old('where_us.brochure_text.' . $en, $whereData[$en]['brochure']['text'] ?? ''),
                        'ar' => old('where_us.brochure_text.' . $ar, $whereData[$ar]['brochure']['text'] ?? ''),
                    ]" />

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="where_us[brochure_icon]"
                            :label="\App\Support\Lang::t('admin.cms.home.labels.brochure_icon', 'Brochure Icon')"
                            input-id="where_brochure_icon"
                            kind="image"
                            max-size-hint="5 MB"
                            :preview-path="media_path($whereData[$primaryLocale]['brochure']['icon_path'] ?? '')" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="where_us[brochure_file]"
                            :label="\App\Support\Lang::t('admin.cms.home.labels.brochure_file', 'Brochure File (PDF)')"
                            input-id="where_brochure_file"
                            kind="file"
                            accept="application/pdf"
                            accepted-types-hint="PDF"
                            max-size-hint="20 MB"
                            :preview-path="media_path($whereData[$primaryLocale]['brochure']['brochure_path'] ?? '')" />

                        <div class="form-group">
                            <label for="where_brochure_link">
                                {{ \App\Support\Lang::t('admin.cms.home.labels.brochure_external_link', 'Brochure external link') }}
                            </label>
                            <input type="text"
                                   id="where_brochure_link"
                                   name="where_us[brochure_link]"
                                   dir="ltr"
                                   @class(['form-control', 'is-invalid' => $errors->has('where_us.brochure_link')])
                                   value="{{ old('where_us.brochure_link', $whereData[$primaryLocale]['brochure']['brochure_path'] ?? '') }}">
                            @error('where_us.brochure_link')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div data-repeater-items="where">
                    <p class="text-muted small" data-repeater-empty @if (count($whereItems)) hidden @endif>
                        {{ __('admin.cms.ui.no_items_yet') }}
                    </p>

                    @foreach ($whereItems as $whereIndex => $item)
                        @php
                            $itemData = $whereItemsData[$item->id] ?? [];
                            $currentImage = $itemData[$primaryLocale]['image_path'] ?? '';
                            $base = "where_us[items][$whereIndex]";
                            $dot = "where_us.items.$whereIndex";
                        @endphp

                        <div class="card mb-3 bg-light" data-repeater-item>
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <span class="font-weight-bold">
                                    {{ __('admin.cms.ui.item_reference', ['id' => $item->id]) }}
                                </span>
                                @include('admin.cms.partials.repeater-remove')
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="{{ $base }}[id]" value="{{ $item->id }}">
                                <input type="hidden" name="{{ $base }}[existing_image]" value="{{ $currentImage }}">

                                <div class="form-row">
                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label for="where_item_{{ $whereIndex }}_order">{{ __('admin.forms.order') }}</label>
                                            <input type="number"
                                                   id="where_item_{{ $whereIndex }}_order"
                                                   name="{{ $base }}[order]"
                                                   class="form-control"
                                                   min="0"
                                                   value="{{ old("$dot.order", $item->order) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <x-admin.file-uploader
                                            :name="$base . '[image]'"
                                            :label="__('admin.forms.image')"
                                            :input-id="'where_item_' . $whereIndex . '_image'"
                                            kind="image"
                                            max-size-hint="10 MB"
                                            :preview-path="media_path($currentImage)" />
                                    </div>
                                </div>

                                <x-admin.translatable-field
                                    label-key="admin.cms.shared.overlay_text_locale"
                                    :id-prefix="'where_overlay_' . $whereIndex"
                                    :names="['en' => $base . '[overlay][' . $en . ']', 'ar' => $base . '[overlay][' . $ar . ']']"
                                    :values="[
                                        'en' => old($dot . '.overlay.' . $en, $itemData[$en]['overlay_text'] ?? ''),
                                        'ar' => old($dot . '.overlay.' . $ar, $itemData[$ar]['overlay_text'] ?? ''),
                                    ]" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="form-text text-muted">{{ __('admin.cms.ui.unsaved_items_hint') }}</small>
            </x-admin.cms-section-card>

            {{-- CTA ---------------------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.home.sections.cta', 'CTA Section')"
                :section-type="$cta?->type"
                :section-id="$cta?->id"
                :is-active="$cta?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="cta[image]"
                            :label="\App\Support\Lang::t('admin.cms.home.labels.main_image', 'Main Image')"
                            input-id="cta_image"
                            kind="image"
                            max-size-hint="10 MB"
                            :preview-path="media_path($ctaData[$primaryLocale]['image_path'] ?? '')" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="cta[overlay_image]"
                            :label="\App\Support\Lang::t('admin.cms.home.labels.overlay_image', 'Overlay Image')"
                            input-id="cta_overlay_image"
                            kind="image"
                            max-size-hint="10 MB"
                            :preview-path="media_path($ctaData[$primaryLocale]['overlay_image_path'] ?? '')" />
                    </div>
                </div>

                @foreach ([
                    'title' => ['key' => 'admin.cms.shared.title_locale', 'type' => 'text'],
                    'text' => ['key' => 'admin.cms.shared.text_locale', 'type' => 'textarea'],
                    'link_text' => ['key' => 'admin.cms.shared.link_text_locale', 'type' => 'text'],
                    'link_url' => ['key' => 'admin.cms.shared.link_url_locale', 'type' => 'text'],
                ] as $field => $meta)
                    <x-admin.translatable-field
                        :type="$meta['type']"
                        :label-key="$meta['key']"
                        :id-prefix="'cta_' . $field"
                        :names="['en' => 'cta[' . $en . '][' . $field . ']', 'ar' => 'cta[' . $ar . '][' . $field . ']']"
                        :values="[
                            'en' => old('cta.' . $en . '.' . $field, $ctaData[$en][$field] ?? ''),
                            'ar' => old('cta.' . $ar . '.' . $field, $ctaData[$ar][$field] ?? ''),
                        ]" />
                @endforeach
            </x-admin.cms-section-card>

            <div class="card">
                <div class="card-body">
                    <x-admin.group-btn class="justify-content-end">
                        <x-admin.btn
                            :href="route('admin.dashboard', ['lang' => app()->getLocale()])"
                            variant="light"
                            icon="arrow-left">
                            {{ __('admin.buttons.back') }}
                        </x-admin.btn>
                        <x-admin.btn type="submit" icon="save">
                            {{ __('admin.forms.save_button') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>
            </div>
        </form>
    </div>

    {{-- Repeater templates: __INDEX__ is replaced client side with a --}}
    {{-- timestamp so new rows never collide with the rendered indexes. --}}
    <template data-repeater-template="partners">
        <div class="card mb-3 bg-light" data-repeater-item>
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="font-weight-bold">{{ __('admin.cms.ui.new_item') }}</span>
                @include('admin.cms.partials.repeater-remove')
            </div>
            <div class="card-body">
                <input type="hidden" name="partners[items][__INDEX__][existing_image]">

                <div class="form-row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="partners_item___INDEX___order">{{ __('admin.forms.order') }}</label>
                            <input type="number"
                                   id="partners_item___INDEX___order"
                                   name="partners[items][__INDEX__][order]"
                                   class="form-control"
                                   min="0">
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <x-admin.file-uploader
                            name="partners[items][__INDEX__][image]"
                            :label="\App\Support\Lang::t('admin.cms.home.labels.slide_image', 'Slide Image')"
                            input-id="partners_item___INDEX___image"
                            kind="image"
                            max-size-hint="10 MB" />
                    </div>
                </div>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.alt_locale"
                    id-prefix="partners_alt___INDEX__"
                    :names="['en' => 'partners[items][__INDEX__][alt][' . $en . ']', 'ar' => 'partners[items][__INDEX__][alt][' . $ar . ']']" />
            </div>
        </div>
    </template>

    <template data-repeater-template="stats">
        <div class="card mb-3 bg-light" data-repeater-item>
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="font-weight-bold">{{ __('admin.cms.ui.new_item') }}</span>
                @include('admin.cms.partials.repeater-remove')
            </div>
            <div class="card-body">
                <input type="hidden" name="stats[items][__INDEX__][existing_icon]">

                <div class="form-row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="stats_item___INDEX___order">{{ __('admin.forms.order') }}</label>
                            <input type="number"
                                   id="stats_item___INDEX___order"
                                   name="stats[items][__INDEX__][order]"
                                   class="form-control"
                                   min="0">
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <x-admin.file-uploader
                            name="stats[items][__INDEX__][icon]"
                            :label="__('admin.forms.icon')"
                            input-id="stats_item___INDEX___icon"
                            kind="image"
                            max-size-hint="5 MB" />
                    </div>
                </div>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.number_locale"
                    id-prefix="stats_number___INDEX__"
                    :names="['en' => 'stats[items][__INDEX__][number][' . $en . ']', 'ar' => 'stats[items][__INDEX__][number][' . $ar . ']']" />

                <x-admin.translatable-field
                    label-key="admin.cms.shared.label_locale"
                    id-prefix="stats_label___INDEX__"
                    :names="['en' => 'stats[items][__INDEX__][label][' . $en . ']', 'ar' => 'stats[items][__INDEX__][label][' . $ar . ']']" />
            </div>
        </div>
    </template>

    <template data-repeater-template="where">
        <div class="card mb-3 bg-light" data-repeater-item>
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="font-weight-bold">{{ __('admin.cms.ui.new_item') }}</span>
                @include('admin.cms.partials.repeater-remove')
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="where_item___INDEX___order">{{ __('admin.forms.order') }}</label>
                            <input type="number"
                                   id="where_item___INDEX___order"
                                   name="where_us[items][__INDEX__][order]"
                                   class="form-control"
                                   min="0">
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <x-admin.file-uploader
                            name="where_us[items][__INDEX__][image]"
                            :label="__('admin.forms.image')"
                            input-id="where_item___INDEX___image"
                            kind="image"
                            max-size-hint="10 MB" />
                    </div>
                </div>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.overlay_text_locale"
                    id-prefix="where_overlay___INDEX__"
                    :names="['en' => 'where_us[items][__INDEX__][overlay][' . $en . ']', 'ar' => 'where_us[items][__INDEX__][overlay][' . $ar . ']']" />
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/cms-admin.js') }}"></script>
@endpush
