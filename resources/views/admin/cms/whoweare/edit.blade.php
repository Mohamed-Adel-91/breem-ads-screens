@extends('admin.layouts.master')

@section('title', $page->name)

@section('content')
    @php
        // Media paths are stored once per section and read from the primary
        // locale, exactly as the controller writes them.
        $primaryLocale = config('app.locale');
        $en = in_array('en', $locales ?? []) ? 'en' : ($locales[0] ?? 'en');
        $ar = in_array('ar', $locales ?? []) ? 'ar' : ($locales[1] ?? $en);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $page->name,
            'subtitle' => __('admin.cms.ui.who_we_are_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.website_cms')],
                ['label' => __('admin.sidebar.who_we_are')],
            ],
        ])

        <form method="POST"
              action="{{ route('admin.cms.whoweare.update', ['lang' => app()->getLocale()]) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Second banner ------------------------------------------------ --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.who_we_are.sections.second_banner', 'Second Banner')"
                :section-type="$banner?->type"
                :section-id="$banner?->id"
                :is-active="$banner?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="banner[image]"
                            :label="\App\Support\Lang::t('admin.cms.who_we_are.labels.banner_image', 'Banner Image')"
                            input-id="whoweare_banner_image"
                            kind="image"
                            max-size-hint="15 MB"
                            :preview-path="media_path($bannerData[$primaryLocale]['image_path'] ?? '')" />
                    </div>
                </div>
            </x-admin.cms-section-card>

            {{-- Who we section ----------------------------------------------- --}}
            @php $whoItems = $whoWe?->items ?? []; @endphp

            <x-admin.cms-section-card
                id="who-we-section"
                :title="\App\Support\Lang::t('admin.cms.who_we_are.sections.who_we_section', 'Who We Section')"
                :section-type="$whoWe?->type"
                :section-id="$whoWe?->id"
                :is-active="$whoWe?->is_active">

                <x-slot:actions>
                    <x-admin.badge variant="light">
                        {{ __('admin.cms.ui.items_count', ['count' => count($whoItems)]) }}
                    </x-admin.badge>
                    <x-admin.btn size="sm" icon="plus" data-repeater-add="who">
                        {{ __('admin.cms.ui.add_item') }}
                    </x-admin.btn>
                </x-slot:actions>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.title_locale"
                    id-prefix="who_we_title"
                    :names="['en' => 'who_we[' . $en . '][title]', 'ar' => 'who_we[' . $ar . '][title]']"
                    :values="[
                        'en' => old('who_we.' . $en . '.title', $whoWeData[$en]['title'] ?? ''),
                        'ar' => old('who_we.' . $ar . '.title', $whoWeData[$ar]['title'] ?? ''),
                    ]" />

                <x-admin.translatable-field
                    type="textarea"
                    rows="4"
                    label-key="admin.cms.shared.description_locale"
                    id-prefix="who_we_description"
                    :names="['en' => 'who_we[' . $en . '][description]', 'ar' => 'who_we[' . $ar . '][description]']"
                    :values="[
                        'en' => old('who_we.' . $en . '.description', $whoWeData[$en]['description'] ?? ''),
                        'ar' => old('who_we.' . $ar . '.description', $whoWeData[$ar]['description'] ?? ''),
                    ]" />

                <hr>

                <div data-repeater-items="who">
                    <p class="text-muted small" data-repeater-empty @if (count($whoItems)) hidden @endif>
                        {{ __('admin.cms.ui.no_items_yet') }}
                    </p>

                    @foreach ($whoItems as $whoIndex => $item)
                        @php
                            $itemData = $whoWeItems[$item->id] ?? [];
                            $base = "who_we[items][$whoIndex]";
                            $dot = "who_we.items.$whoIndex";

                            // Bullets are stored as an array and edited as one line per bullet.
                            $bulletsEn = isset($itemData[$en]['bullets']) ? implode("\n", $itemData[$en]['bullets']) : '';
                            $bulletsAr = isset($itemData[$ar]['bullets']) ? implode("\n", $itemData[$ar]['bullets']) : '';
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

                                <div class="form-row">
                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label for="who_item_{{ $whoIndex }}_order">{{ __('admin.forms.order') }}</label>
                                            <input type="number"
                                                   id="who_item_{{ $whoIndex }}_order"
                                                   name="{{ $base }}[order]"
                                                   class="form-control"
                                                   min="0"
                                                   value="{{ old("$dot.order", $item->order) }}">
                                        </div>
                                    </div>
                                </div>

                                <x-admin.translatable-field
                                    label-key="admin.cms.shared.title_locale"
                                    :id-prefix="'who_item_title_' . $whoIndex"
                                    :names="['en' => $base . '[title][' . $en . ']', 'ar' => $base . '[title][' . $ar . ']']"
                                    :values="[
                                        'en' => old($dot . '.title.' . $en, $itemData[$en]['title'] ?? ''),
                                        'ar' => old($dot . '.title.' . $ar, $itemData[$ar]['title'] ?? ''),
                                    ]" />

                                <x-admin.translatable-field
                                    type="textarea"
                                    label-key="admin.cms.shared.text_locale"
                                    :id-prefix="'who_item_text_' . $whoIndex"
                                    :names="['en' => $base . '[text][' . $en . ']', 'ar' => $base . '[text][' . $ar . ']']"
                                    :values="[
                                        'en' => old($dot . '.text.' . $en, $itemData[$en]['text'] ?? ''),
                                        'ar' => old($dot . '.text.' . $ar, $itemData[$ar]['text'] ?? ''),
                                    ]" />

                                <x-admin.translatable-field
                                    type="textarea"
                                    label-key="admin.cms.shared.bullets_locale"
                                    :help="__('admin.cms.shared.bullets_help_text')"
                                    :id-prefix="'who_item_bullets_' . $whoIndex"
                                    :names="['en' => $base . '[bullets][' . $en . ']', 'ar' => $base . '[bullets][' . $ar . ']']"
                                    :values="[
                                        'en' => old($dot . '.bullets.' . $en, $bulletsEn),
                                        'ar' => old($dot . '.bullets.' . $ar, $bulletsAr),
                                    ]" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="form-text text-muted">{{ __('admin.cms.ui.unsaved_items_hint') }}</small>
            </x-admin.cms-section-card>

            {{-- Portfolio image ---------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.who_we_are.labels.portfolio_image', 'Portfolio Image')"
                :section-type="$port?->type"
                :section-id="$port?->id"
                :is-active="$port?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="port[image]"
                            :label="__('admin.forms.image')"
                            input-id="whoweare_port_image"
                            kind="image"
                            max-size-hint="15 MB"
                            :preview-path="media_path($portData[$primaryLocale]['image_path'] ?? '')" />
                    </div>
                </div>
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

    <template data-repeater-template="who">
        <div class="card mb-3 bg-light" data-repeater-item>
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="font-weight-bold">{{ __('admin.cms.ui.new_item') }}</span>
                @include('admin.cms.partials.repeater-remove')
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="who_item___INDEX___order">{{ __('admin.forms.order') }}</label>
                            <input type="number"
                                   id="who_item___INDEX___order"
                                   name="who_we[items][__INDEX__][order]"
                                   class="form-control"
                                   min="0">
                        </div>
                    </div>
                </div>

                <x-admin.translatable-field
                    label-key="admin.cms.shared.title_locale"
                    id-prefix="who_item_title___INDEX__"
                    :names="['en' => 'who_we[items][__INDEX__][title][' . $en . ']', 'ar' => 'who_we[items][__INDEX__][title][' . $ar . ']']" />

                <x-admin.translatable-field
                    type="textarea"
                    label-key="admin.cms.shared.text_locale"
                    id-prefix="who_item_text___INDEX__"
                    :names="['en' => 'who_we[items][__INDEX__][text][' . $en . ']', 'ar' => 'who_we[items][__INDEX__][text][' . $ar . ']']" />

                <x-admin.translatable-field
                    type="textarea"
                    label-key="admin.cms.shared.bullets_locale"
                    :help="__('admin.cms.shared.bullets_help_text')"
                    id-prefix="who_item_bullets___INDEX__"
                    :names="['en' => 'who_we[items][__INDEX__][bullets][' . $en . ']', 'ar' => 'who_we[items][__INDEX__][bullets][' . $ar . ']']" />
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/cms-admin.js') }}"></script>
@endpush
