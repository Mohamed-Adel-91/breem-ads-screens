@extends('admin.layouts.master')
@section('content')
    @php
        $primaryLocale = config('app.locale');
        $englishLocale = in_array('en', $locales ?? []) ? 'en' : ($locales[0] ?? 'en');
        $arabicLocale = in_array('ar', $locales ?? []) ? 'ar' : ($locales[1] ?? $englishLocale);
    @endphp
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.cms.whoweare.update', ['lang' => app()->getLocale()]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">@t('admin.cms.who_we_are.sections.second_banner', 'Second Banner')</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => \App\Support\Lang::t('admin.cms.who_we_are.labels.banner_image', 'Banner Image'),
                                'name' => 'banner[image]',
                                'inputId' => 'whoweare_banner_image',
                                'previewPath' => media_path($bannerData[$primaryLocale]['image_path'] ?? ''),
                            ])
                        </div>
                    </div>

                    <div class="card mb-4" id="who-we-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">@t('admin.cms.who_we_are.sections.who_we_section', 'Who We Section')</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-who-item">{{ __('admin.buttons.new') }}</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 flex-md-row-reverse">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                        <input type="text" class="form-control" dir="rtl"
                                            name="who_we[{{ $arabicLocale }}][title]"
                                            value="{{ old("who_we.$arabicLocale.title", $whoWeData[$arabicLocale]['title'] ?? '') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">{{ __('admin.cms.shared.description_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                        <textarea class="form-control" rows="4" dir="rtl"
                                            name="who_we[{{ $arabicLocale }}][description]">{{ old("who_we.$arabicLocale.description", $whoWeData[$arabicLocale]['description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                        <input type="text" class="form-control" name="who_we[{{ $englishLocale }}][title]"
                                            value="{{ old("who_we.$englishLocale.title", $whoWeData[$englishLocale]['title'] ?? '') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">{{ __('admin.cms.shared.description_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                        <textarea class="form-control" rows="4"
                                            name="who_we[{{ $englishLocale }}][description]">{{ old("who_we.$englishLocale.description", $whoWeData[$englishLocale]['description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div id="who-items-container">
                                @php $whoIndex = 0; @endphp
                                @foreach ($whoWe?->items ?? [] as $item)
                                    @php $itemData = $whoWeItems[$item->id] ?? []; @endphp
                                    <div class="card mb-3 who-item" data-index="{{ $whoIndex }}">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>#{{ $item->id }}</strong>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-who-item">&times;</button>
                                        </div>
                                        <div class="card-body">
                                            <input type="hidden" name="who_we[items][{{ $whoIndex }}][id]" value="{{ $item->id }}">
                                            <div class="row g-3 align-items-start">
                                                <div class="col-md-3">
                                                    <label class="form-label">{{ __('admin.forms.order') }}</label>
                                                    <input type="number" class="form-control" name="who_we[items][{{ $whoIndex }}][order]"
                                                        value="{{ old("who_we.items.$whoIndex.order", $item->order) }}">
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="row g-3 flex-md-row-reverse">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                                                <input type="text" class="form-control" dir="rtl"
                                                                    name="who_we[items][{{ $whoIndex }}][title][{{ $arabicLocale }}]"
                                                                    value="{{ old("who_we.items.$whoIndex.title.$arabicLocale", $itemData[$arabicLocale]['title'] ?? '') }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('admin.cms.shared.text_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                                                <textarea class="form-control" rows="3" dir="rtl"
                                                                    name="who_we[items][{{ $whoIndex }}][text][{{ $arabicLocale }}]">{{ old("who_we.items.$whoIndex.text.$arabicLocale", $itemData[$arabicLocale]['text'] ?? '') }}</textarea>
                                                            </div>
                                                            <div>
                                                                <label class="form-label">{{ __('admin.cms.shared.bullets_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                                                <textarea class="form-control" rows="3" dir="rtl"
                                                                    name="who_we[items][{{ $whoIndex }}][bullets][{{ $arabicLocale }}]">{{ old("who_we.items.$whoIndex.bullets.$arabicLocale", isset($itemData[$arabicLocale]['bullets']) ? implode("\n", $itemData[$arabicLocale]['bullets']) : '') }}</textarea>
                                                                <small class="text-muted">{{ __('admin.cms.shared.bullets_help_text') }}</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                                                <input type="text" class="form-control"
                                                                    name="who_we[items][{{ $whoIndex }}][title][{{ $englishLocale }}]"
                                                                    value="{{ old("who_we.items.$whoIndex.title.$englishLocale", $itemData[$englishLocale]['title'] ?? '') }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('admin.cms.shared.text_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                                                <textarea class="form-control" rows="3"
                                                                    name="who_we[items][{{ $whoIndex }}][text][{{ $englishLocale }}]">{{ old("who_we.items.$whoIndex.text.$englishLocale", $itemData[$englishLocale]['text'] ?? '') }}</textarea>
                                                            </div>
                                                            <div>
                                                                <label class="form-label">{{ __('admin.cms.shared.bullets_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                                                <textarea class="form-control" rows="3"
                                                                    name="who_we[items][{{ $whoIndex }}][bullets][{{ $englishLocale }}]">{{ old("who_we.items.$whoIndex.bullets.$englishLocale", isset($itemData[$englishLocale]['bullets']) ? implode("\n", $itemData[$englishLocale]['bullets']) : '') }}</textarea>
                                                                <small class="text-muted">{{ __('admin.cms.shared.bullets_help_text') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @php $whoIndex++; @endphp
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">@t('admin.cms.who_we_are.labels.portfolio_image', 'Portfolio Image')</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => __('admin.forms.image'),
                                'name' => 'port[image]',
                                'inputId' => 'whoweare_port_image',
                                'previewPath' => media_path($portData[$primaryLocale]['image_path'] ?? ''),
                            ])
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
    <template id="who-item-template">
        <div class="card mb-3 who-item" data-index="__INDEX__">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ __('admin.buttons.new') }}</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-who-item">&times;</button>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-start">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.forms.order') }}</label>
                        <input type="number" class="form-control" name="who_we[items][__INDEX__][order]">
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3 flex-md-row-reverse">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                    <input type="text" class="form-control" dir="rtl"
                                        name="who_we[items][__INDEX__][title][{{ $arabicLocale }}]">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('admin.cms.shared.text_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                    <textarea class="form-control" rows="3" dir="rtl"
                                        name="who_we[items][__INDEX__][text][{{ $arabicLocale }}]"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">{{ __('admin.cms.shared.bullets_locale', ['locale' => strtoupper($arabicLocale)]) }}</label>
                                    <textarea class="form-control" rows="3" dir="rtl"
                                        name="who_we[items][__INDEX__][bullets][{{ $arabicLocale }}]"></textarea>
                                    <small class="text-muted">{{ __('admin.cms.shared.bullets_help_text') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('admin.cms.shared.title_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                    <input type="text" class="form-control"
                                        name="who_we[items][__INDEX__][title][{{ $englishLocale }}]">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('admin.cms.shared.text_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                    <textarea class="form-control" rows="3"
                                        name="who_we[items][__INDEX__][text][{{ $englishLocale }}]"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">{{ __('admin.cms.shared.bullets_locale', ['locale' => strtoupper($englishLocale)]) }}</label>
                                    <textarea class="form-control" rows="3"
                                        name="who_we[items][__INDEX__][bullets][{{ $englishLocale }}]"></textarea>
                                    <small class="text-muted">{{ __('admin.cms.shared.bullets_help_text') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addButton = document.getElementById('add-who-item');
            const container = document.getElementById('who-items-container');
            const template = document.getElementById('who-item-template');

            if (addButton && container && template) {
                addButton.addEventListener('click', function () {
                    const index = Date.now();
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, index).trim();
                    container.appendChild(wrapper.firstElementChild);
                });
            }

            container?.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-who-item')) {
                    event.target.closest('.who-item').remove();
                }
            });
        });
    </script>
@endpush
