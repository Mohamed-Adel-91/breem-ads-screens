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
                <form method="POST" action="{{ route('admin.cms.whoweare.update', ['lang' => app()->getLocale()]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Second Banner</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => 'Banner Image',
                                'name' => 'banner[image]',
                                'inputId' => 'whoweare_banner_image',
                                'previewPath' => media_path($bannerData[$primaryLocale]['image_path'] ?? ''),
                            ])
                        </div>
                    </div>

                    <div class="card mb-4" id="who-we-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Who We Section</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-who-item">{{ __('admin.buttons.new') }}</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($locales as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                                        <input type="text" class="form-control" name="who_we[{{ $locale }}][title]"
                                            value="{{ old("who_we.$locale.title", $whoWeData[$locale]['title'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control" rows="4" name="who_we[{{ $locale }}][description]">{{ old("who_we.$locale.description", $whoWeData[$locale]['description'] ?? '') }}</textarea>
                                    </div>
                                @endforeach
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
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Order</label>
                                                    <input type="number" class="form-control" name="who_we[items][{{ $whoIndex }}][order]"
                                                        value="{{ old("who_we.items.$whoIndex.order", $item->order) }}">
                                                </div>
                                                @foreach ($locales as $locale)
                                                    <div class="col-md-6">
                                                        <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                                                        <input type="text" class="form-control" name="who_we[items][{{ $whoIndex }}][title][{{ $locale }}]"
                                                            value="{{ old("who_we.items.$whoIndex.title.$locale", $itemData[$locale]['title'] ?? '') }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Text ({{ strtoupper($locale) }})</label>
                                                        <textarea class="form-control" rows="3" name="who_we[items][{{ $whoIndex }}][text][{{ $locale }}]">{{ old("who_we.items.$whoIndex.text.$locale", $itemData[$locale]['text'] ?? '') }}</textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Bullets ({{ strtoupper($locale) }})</label>
                                                        <textarea class="form-control" rows="3" name="who_we[items][{{ $whoIndex }}][bullets][{{ $locale }}]">{{ old("who_we.items.$whoIndex.bullets.$locale", isset($itemData[$locale]['bullets']) ? implode("\n", $itemData[$locale]['bullets']) : '') }}</textarea>
                                                        <small class="text-muted">Enter each bullet on a new line.</small>
                                                    </div>
                                                @endforeach
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
                            <h5 class="mb-0">Portfolio Image</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.layouts.components.media-upload', [
                                'label' => 'Image',
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
                <strong>New</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-who-item">&times;</button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Order</label>
                        <input type="number" class="form-control" name="who_we[items][__INDEX__][order]">
                    </div>
                    @foreach ($locales as $locale)
                        <div class="col-md-6">
                            <label class="form-label">Title ({{ strtoupper($locale) }})</label>
                            <input type="text" class="form-control" name="who_we[items][__INDEX__][title][{{ $locale }}]">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Text ({{ strtoupper($locale) }})</label>
                            <textarea class="form-control" rows="3" name="who_we[items][__INDEX__][text][{{ $locale }}]"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bullets ({{ strtoupper($locale) }})</label>
                            <textarea class="form-control" rows="3" name="who_we[items][__INDEX__][bullets][{{ $locale }}]"></textarea>
                            <small class="text-muted">Enter each bullet on a new line.</small>
                        </div>
                    @endforeach
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
