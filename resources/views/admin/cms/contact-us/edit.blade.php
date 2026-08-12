@extends('admin.layouts.master')

@section('title', $page->name)

@section('content')
    @php
        // Media paths are stored once per section and read from the primary
        // locale, exactly as the controller writes them.
        $primaryLocale = config('app.locale');
        $ar = 'ar';
        $en = 'en';

        // The four enquiry forms share one editing shape. Their label / radio /
        // option keys are discovered from the stored data plus any old() input,
        // exactly as the legacy view did — no keys are invented here.
        $forms = [
            'ads' => ['title' => \App\Support\Lang::t('admin.cms.contact.forms.ads', 'Ads Subscription Form'), 'data' => $adsData, 'section' => $adsForm],
            'screens' => ['title' => \App\Support\Lang::t('admin.cms.contact.forms.screens', 'Screens Subscription Form'), 'data' => $screensData, 'section' => $screensForm],
            'create' => ['title' => \App\Support\Lang::t('admin.cms.contact.forms.create', 'Ad Creation Request'), 'data' => $createData, 'section' => $createForm],
            'faq' => ['title' => \App\Support\Lang::t('admin.cms.contact.forms.faq', 'FAQs Form'), 'data' => $faqData, 'section' => $faqForm],
        ];

        $mergeKeys = function (array $arData, array $enData, string $group, string $key) use ($ar, $en) {
            $keys = array_keys($arData[$group] ?? []);

            foreach (array_keys($enData[$group] ?? []) as $candidate) {
                if (!in_array($candidate, $keys, true)) {
                    $keys[] = $candidate;
                }
            }

            foreach ([$ar, $en] as $locale) {
                $old = old("contact_forms.$key.$locale.$group", []);

                if (is_array($old)) {
                    foreach (array_keys($old) as $candidate) {
                        if (!in_array($candidate, $keys, true)) {
                            $keys[] = $candidate;
                        }
                    }
                }
            }

            return $keys;
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $page->name,
            'subtitle' => __('admin.cms.ui.contact_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.website_cms')],
                ['label' => __('admin.sidebar.contact_us')],
            ],
        ])

        <form method="POST"
              action="{{ route('admin.cms.contact.update', ['lang' => app()->getLocale()]) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Top banner --------------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.contact.sections.top_banner', 'Top Banner')"
                :section-type="$banner?->type"
                :section-id="$banner?->id"
                :is-active="$banner?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="banner[image]"
                            :label="\App\Support\Lang::t('admin.cms.contact.labels.banner_image', 'Banner Image')"
                            input-id="contact_banner_image"
                            kind="image"
                            max-size-hint="20 MB"
                            :preview-path="media_path($bannerData[$primaryLocale]['image_path'] ?? '')" />
                    </div>
                </div>
            </x-admin.cms-section-card>

            {{-- Contact heading ---------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.contact.sections.contact_heading', 'Contact Heading')"
                :section-type="$contact?->type"
                :section-id="$contact?->id"
                :is-active="$contact?->is_active">

                @foreach ([
                    'title' => ['key' => 'admin.cms.shared.title_locale', 'type' => 'text'],
                    'subtitle' => ['key' => 'admin.cms.shared.subtitle_locale', 'type' => 'text'],
                ] as $field => $meta)
                    <x-admin.translatable-field
                        :type="$meta['type']"
                        :label-key="$meta['key']"
                        :id-prefix="'contact_' . $field"
                        :names="['en' => 'contact[' . $en . '][' . $field . ']', 'ar' => 'contact[' . $ar . '][' . $field . ']']"
                        :values="[
                            'en' => old('contact.' . $en . '.' . $field, $contactData[$en][$field] ?? ''),
                            'ar' => old('contact.' . $ar . '.' . $field, $contactData[$ar][$field] ?? ''),
                        ]" />
                @endforeach
            </x-admin.cms-section-card>

            {{-- Map & location ------------------------------------------------ --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.contact.sections.map_location', 'Map & Location')"
                :section-type="$map?->type"
                :section-id="$map?->id"
                :is-active="$map?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="map[background_image]"
                            :label="\App\Support\Lang::t('admin.cms.contact.labels.background_image', 'Background Image')"
                            input-id="contact_map_background"
                            kind="image"
                            max-size-hint="20 MB"
                            :preview-path="media_path($mapData[$primaryLocale]['background_image_path'] ?? '')" />
                    </div>
                </div>

                @foreach ([
                    'title' => ['key' => 'admin.cms.shared.title_locale', 'type' => 'text'],
                    'address' => ['key' => 'admin.cms.shared.address_locale', 'type' => 'textarea'],
                    'phone_label' => ['key' => 'admin.cms.shared.phone_label_locale', 'type' => 'text'],
                    'whatsapp_label' => ['key' => 'admin.cms.shared.whatsapp_label_locale', 'type' => 'text'],
                ] as $field => $meta)
                    <x-admin.translatable-field
                        :type="$meta['type']"
                        :label-key="$meta['key']"
                        :id-prefix="'map_' . $field"
                        :names="['en' => 'map[' . $en . '][' . $field . ']', 'ar' => 'map[' . $ar . '][' . $field . ']']"
                        :values="[
                            'en' => old('map.' . $en . '.' . $field, $mapData[$en][$field] ?? ''),
                            'ar' => old('map.' . $ar . '.' . $field, $mapData[$ar][$field] ?? ''),
                        ]" />
                @endforeach
            </x-admin.cms-section-card>

            {{-- Bottom banner ------------------------------------------------- --}}
            <x-admin.cms-section-card
                :title="\App\Support\Lang::t('admin.cms.contact.sections.bottom_banner', 'Bottom Banner')"
                :section-type="$bottom?->type"
                :section-id="$bottom?->id"
                :is-active="$bottom?->is_active">

                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <x-admin.file-uploader
                            name="bottom[image]"
                            :label="\App\Support\Lang::t('admin.cms.contact.labels.banner_image', 'Banner Image')"
                            input-id="contact_bottom_image"
                            kind="image"
                            max-size-hint="20 MB"
                            :preview-path="media_path($bottomData[$primaryLocale]['image_path'] ?? '')" />
                    </div>
                </div>
            </x-admin.cms-section-card>

            {{-- Enquiry forms -------------------------------------------------- --}}
            @foreach ($forms as $key => $payload)
                @php
                    $sectionData = $payload['data'];
                    $sectionAr = $sectionData[$ar] ?? [];
                    $sectionEn = $sectionData[$en] ?? [];

                    $labelKeys = $mergeKeys($sectionAr, $sectionEn, 'labels', $key);
                    $radioKeys = $mergeKeys($sectionAr, $sectionEn, 'radio', $key);
                    $optionKeys = $mergeKeys($sectionAr, $sectionEn, 'options', $key);

                    $base = "contact_forms[$key]";
                    $dot = "contact_forms.$key";
                @endphp

                <x-admin.cms-section-card
                    :title="$payload['title']"
                    :section-type="$payload['section']?->type"
                    :section-id="$payload['section']?->id"
                    :is-active="$payload['section']?->is_active">

                    <div class="form-row">
                        <div class="col-12 col-md-6">
                            <x-admin.file-uploader
                                :name="$base . '[card_image1]'"
                                :label="\App\Support\Lang::t('admin.cms.contact.labels.card_image1', 'Card Image 1')"
                                :input-id="'contact_' . $key . '_image1'"
                                kind="image"
                                max-size-hint="20 MB"
                                :preview-path="media_path($sectionData[$primaryLocale]['card_image1'] ?? '')" />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-admin.file-uploader
                                :name="$base . '[card_image2]'"
                                :label="\App\Support\Lang::t('admin.cms.contact.labels.card_image2', 'Card Image 2')"
                                :input-id="'contact_' . $key . '_image2'"
                                kind="image"
                                max-size-hint="20 MB"
                                :preview-path="media_path($sectionData[$primaryLocale]['card_image2'] ?? '')" />
                        </div>
                    </div>

                    @foreach ([
                        'card_text' => ['key' => 'admin.cms.shared.card_text_locale', 'type' => 'textarea'],
                        'modal_title' => ['key' => 'admin.cms.shared.modal_title_locale', 'type' => 'text'],
                        'submit_text' => ['key' => 'admin.cms.shared.submit_text_locale', 'type' => 'text'],
                    ] as $field => $meta)
                        <x-admin.translatable-field
                            :type="$meta['type']"
                            :label-key="$meta['key']"
                            :id-prefix="'contact_' . $key . '_' . $field"
                            :names="['en' => $base . '[' . $en . '][' . $field . ']', 'ar' => $base . '[' . $ar . '][' . $field . ']']"
                            :values="[
                                'en' => old($dot . '.' . $en . '.' . $field, $sectionEn[$field] ?? ''),
                                'ar' => old($dot . '.' . $ar . '.' . $field, $sectionAr[$field] ?? ''),
                            ]" />
                    @endforeach

                    @foreach ($labelKeys as $labelKey)
                        <x-admin.translatable-field
                            label-key="admin.cms.shared.label_locale_value"
                            :label-replace="['value' => \Illuminate\Support\Str::headline($labelKey)]"
                            :id-prefix="'contact_' . $key . '_label_' . \Illuminate\Support\Str::slug($labelKey, '_')"
                            :names="['en' => $base . '[' . $en . '][labels][' . $labelKey . ']', 'ar' => $base . '[' . $ar . '][labels][' . $labelKey . ']']"
                            :values="[
                                'en' => old($dot . '.' . $en . '.labels.' . $labelKey, $sectionEn['labels'][$labelKey] ?? ''),
                                'ar' => old($dot . '.' . $ar . '.labels.' . $labelKey, $sectionAr['labels'][$labelKey] ?? ''),
                            ]" />
                    @endforeach

                    @foreach ($radioKeys as $radioKey)
                        <x-admin.translatable-field
                            label-key="admin.cms.shared.radio_locale_value"
                            :label-replace="['value' => \Illuminate\Support\Str::headline($radioKey)]"
                            :id-prefix="'contact_' . $key . '_radio_' . \Illuminate\Support\Str::slug($radioKey, '_')"
                            :names="['en' => $base . '[' . $en . '][radio][' . $radioKey . ']', 'ar' => $base . '[' . $ar . '][radio][' . $radioKey . ']']"
                            :values="[
                                'en' => old($dot . '.' . $en . '.radio.' . $radioKey, $sectionEn['radio'][$radioKey] ?? ''),
                                'ar' => old($dot . '.' . $ar . '.radio.' . $radioKey, $sectionAr['radio'][$radioKey] ?? ''),
                            ]" />
                    @endforeach

                    @foreach ($optionKeys as $optionKey)
                        @php
                            // Options are stored as arrays and edited as one option per line.
                            $rawEn = $sectionEn['options'][$optionKey] ?? null;
                            $rawAr = $sectionAr['options'][$optionKey] ?? null;
                            $optionEn = is_array($rawEn) ? implode("\n", $rawEn) : ($rawEn ?? '');
                            $optionAr = is_array($rawAr) ? implode("\n", $rawAr) : ($rawAr ?? '');
                        @endphp

                        <x-admin.translatable-field
                            type="textarea"
                            label-key="admin.cms.shared.options_locale_value"
                            :label-replace="['value' => \Illuminate\Support\Str::headline($optionKey)]"
                            :help="__('admin.cms.shared.options_help_text')"
                            :id-prefix="'contact_' . $key . '_option_' . \Illuminate\Support\Str::slug($optionKey, '_')"
                            :names="['en' => $base . '[' . $en . '][options][' . $optionKey . ']', 'ar' => $base . '[' . $ar . '][options][' . $optionKey . ']']"
                            :values="[
                                'en' => old($dot . '.' . $en . '.options.' . $optionKey, $optionEn),
                                'ar' => old($dot . '.' . $ar . '.options.' . $optionKey, $optionAr),
                            ]" />
                    @endforeach
                </x-admin.cms-section-card>
            @endforeach

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
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/cms-admin.js') }}"></script>
@endpush
