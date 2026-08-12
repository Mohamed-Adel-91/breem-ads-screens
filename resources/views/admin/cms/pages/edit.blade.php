@extends('admin.layouts.master')

@section('title', $page->name)

@section('content')
    @php
        $locale = app()->getLocale();

        // URL templates are built from the route definitions themselves so the
        // AJAX endpoints stay in step with routes/admin.php.
        $sectionUrl = route('admin.cms.sections.update', ['lang' => $locale, 'section' => '__ID__']);
        $sectionToggleUrl = route('admin.cms.sections.toggle', ['lang' => $locale, 'section' => '__ID__']);
        $itemUrl = route('admin.cms.items.update', ['lang' => $locale, 'item' => '__ID__']);
        $itemToggleUrl = route('admin.cms.items.toggle', ['lang' => $locale, 'item' => '__ID__']);

        $jsMessages = [
            'active' => __('admin.forms.active'),
            'inactive' => __('admin.cms.inactive'),
            'close' => __('admin.sweet_alert.cancel_button'),
            'orderPrompt' => __('admin.cms.ui.order_prompt'),
            'invalidOrder' => __('admin.cms.ui.invalid_order'),
            'orderUpdated' => __('admin.cms.ui.order_updated'),
            'sectionDeleted' => __('admin.cms.ui.section_deleted'),
            'itemDeleted' => __('admin.cms.ui.item_deleted'),
            'deleteSectionConfirm' => __('admin.cms.delete_section_confirm'),
            'deleteItemConfirm' => __('admin.cms.delete_item_confirm'),
            'sectionToggleFailed' => __('admin.cms.section_toggle_failed'),
            'itemToggleFailed' => __('admin.cms.item_toggle_failed'),
            'deleteFailed' => __('admin.cms.delete_failed'),
            'saveFailed' => __('admin.cms.section_data_save_failed'),
            'sectionDataUpdated' => __('admin.cms.section_data_updated'),
            'sectionDataSaveFailed' => __('admin.cms.section_data_save_failed'),
            'invalidJsonFix' => __('admin.cms.invalid_json_fix'),
            'clearDataConfirm' => __('admin.cms.clear_data_confirm') . ' ' . __('admin.cms.clear_data_warning'),
        ];
    @endphp

    <div class="container-fluid"
         data-cms-page-editor
         data-section-url="{{ $sectionUrl }}"
         data-section-toggle-url="{{ $sectionToggleUrl }}"
         data-item-url="{{ $itemUrl }}"
         data-item-toggle-url="{{ $itemToggleUrl }}"
         data-messages="{{ json_encode($jsMessages, JSON_UNESCAPED_UNICODE) }}">

        @include('admin.layouts.page-header', [
            'title' => $page->name,
            'subtitle' => __('admin.cms.ui.pages_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.website_cms')],
                ['label' => $page->name],
            ],
        ])

        <div aria-live="polite" data-cms-notifications></div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title mb-2">
                    {{ __('admin.cms.page_details', ['name' => $page->name, 'slug' => $page->slug]) }}
                </h2>

                {{-- Documented limitations of this screen. Both are backend --}}
                {{-- issues that Phase 4 deliberately leaves untouched. --}}
                <div class="alert alert-warning mb-0" role="alert">
                    <p class="mb-1">{{ __('admin.cms.ui.section_unreachable') }}</p>
                    <p class="mb-0">{{ __('admin.cms.ui.section_upload_defect') }}</p>
                </div>
            </div>
        </div>

        @forelse ($page->sections as $section)
            @php
                $sectionData = is_array($section->section_data)
                    ? $section->section_data
                    : (array) ($section->section_data ?? []);
            @endphp

            <div class="card mb-4" id="section_{{ $section->id }}">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mr-auto">
                        <h2 class="card-title mb-0 d-inline-block">#{{ $section->id }}</h2>
                        <x-admin.badge variant="light" class="ml-2">
                            {{ __('admin.cms.type') }}: <code>{{ $section->type ?? '-' }}</code>
                        </x-admin.badge>
                        <x-admin.badge variant="info" class="ml-1">
                            {{ __('admin.cms.order') }}: <span data-section-order>{{ $section->order }}</span>
                        </x-admin.badge>
                        <x-admin.badge :variant="$section->is_active ? 'success' : 'danger'"
                                       class="ml-1"
                                       data-section-active>
                            {{ $section->is_active ? __('admin.forms.active') : __('admin.cms.inactive') }}
                        </x-admin.badge>
                    </div>

                    <x-admin.group-btn class="mt-2 mt-sm-0">
                        <x-admin.btn variant="outline-primary"
                                     size="sm"
                                     icon="hash"
                                     data-cms-action="order-section"
                                     :data-cms-id="$section->id">
                            {{ __('admin.cms.update_order') }}
                        </x-admin.btn>
                        <x-admin.btn variant="outline-warning"
                                     size="sm"
                                     icon="power"
                                     data-cms-action="toggle-section"
                                     :data-cms-id="$section->id">
                            {{ __('admin.cms.toggle') }}
                        </x-admin.btn>
                        <x-admin.btn variant="outline-danger"
                                     size="sm"
                                     icon="trash-2"
                                     data-cms-action="delete-section"
                                     :data-cms-id="$section->id">
                            {{ __('admin.cms.delete') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                        <h3 class="h6 mb-0">
                            {{ __('admin.cms.section_data', ['locale' => strtoupper($locale)]) }}
                        </h3>

                        <x-admin.group-btn>
                            <x-admin.btn variant="outline-secondary"
                                         size="sm"
                                         data-cms-action="format-section"
                                         :data-cms-id="$section->id">
                                {{ __('admin.cms.format') }}
                            </x-admin.btn>
                            <x-admin.btn variant="outline-danger"
                                         size="sm"
                                         data-cms-action="clear-section"
                                         :data-cms-id="$section->id">
                                {{ __('admin.cms.clear') }}
                            </x-admin.btn>
                            <x-admin.btn size="sm"
                                         icon="save"
                                         data-cms-action="save-section"
                                         :data-cms-id="$section->id">
                                {{ __('admin.forms.save_button') }}
                            </x-admin.btn>
                        </x-admin.group-btn>
                    </div>

                    @if (empty($sectionData))
                        <div class="alert alert-light border">{{ __('admin.cms.no_section_data') }}</div>
                    @else
                        {{-- A bare div, not a <form>: the payload is assembled and --}}
                        {{-- sent by cms-admin.js, and nesting forms is invalid. --}}
                        <div id="secform_{{ $section->id }}" class="row">
                            @foreach ($sectionData as $key => $value)
                                @php
                                    $label = \Illuminate\Support\Str::headline($key);
                                    $fieldId = 'sec_' . $section->id . '_' . $key;
                                    $isBool = is_bool($value)
                                        || in_array($value, [1, 0, '1', '0'], true)
                                        || (is_string($value) && in_array(strtolower($value), ['true', 'false'], true));
                                    $boolChecked = is_bool($value)
                                        ? $value
                                        : in_array($value, [1, '1', 'true', 'TRUE', true], true);
                                @endphp

                                @if ($isBool)
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   id="{{ $fieldId }}"
                                                   data-sec-checkbox
                                                   data-key="{{ $key }}"
                                                   @checked($boolChecked)>
                                            <label for="{{ $fieldId }}" class="form-check-label">{{ $label }}</label>
                                        </div>
                                    </div>
                                @elseif (\Illuminate\Support\Str::contains($key, '_url'))
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="{{ $fieldId }}">{{ $label }}</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="{{ $fieldId }}"
                                                   dir="ltr"
                                                   data-sec-text
                                                   data-key="{{ $key }}"
                                                   value="{{ is_string($value) ? $value : '' }}">
                                        </div>
                                    </div>
                                @elseif (\Illuminate\Support\Str::contains($key, '_path'))
                                    <div class="col-12">
                                        <x-admin.file-uploader
                                            :name="'uploads[' . $key . ']'"
                                            :label="$label"
                                            :input-id="'upload_' . $section->id . '_' . $key"
                                            :preview-path="media_path(is_string($value) ? $value : '')" />
                                        <input type="hidden"
                                               data-sec-current-value
                                               data-key="{{ $key }}"
                                               value="{{ is_string($value) ? $value : '' }}">
                                    </div>
                                @elseif (is_string($value))
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="{{ $fieldId }}">{{ $label }}</label>
                                            <textarea id="{{ $fieldId }}"
                                                      class="form-control"
                                                      rows="3"
                                                      data-sec-text
                                                      data-key="{{ $key }}">{{ $value }}</textarea>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>{{ $label }}</label>
                                            <pre class="bg-light border rounded p-2 mb-0" style="overflow-x: auto;">{{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="form-group mt-3">
                        <label class="sr-only" for="secdata_{{ $section->id }}">
                            {{ __('admin.cms.section_data', ['locale' => strtoupper($locale)]) }}
                        </label>
                        <textarea id="secdata_{{ $section->id }}"
                                  class="form-control text-monospace"
                                  rows="8"
                                  dir="ltr"
                                  spellcheck="false"
                                  @if (!empty($sectionData)) hidden @endif>@json($section->section_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)</textarea>
                        <small class="form-text text-muted">{{ __('admin.cms.edit_json_hint') }}</small>
                    </div>

                    <h3 class="h6 mt-4">{{ __('admin.cms.actions') }}</h3>

                    @if ($section->items->count())
                        <p class="text-muted small">{{ __('admin.cms.ui.item_activation_defect') }}</p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('admin.cms.id') }}</th>
                                        <th scope="col">{{ __('admin.cms.order') }}</th>
                                        <th scope="col">{{ __('admin.cms.ui.section_status') }}</th>
                                        <th scope="col">{{ __('admin.cms.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section->items as $item)
                                        @php
                                            // section_items has no is_active column; the flag lives
                                            // inside the translated data payload.
                                            $itemActive = is_array($item->data)
                                                ? (bool) ($item->data['is_active'] ?? true)
                                                : true;
                                        @endphp
                                        <tr id="item_{{ $item->id }}">
                                            <td>{{ $item->id }}</td>
                                            <td><span data-item-order>{{ $item->order }}</span></td>
                                            <td>
                                                <x-admin.badge :variant="$itemActive ? 'success' : 'danger'" data-item-active>
                                                    {{ $itemActive ? __('admin.forms.active') : __('admin.cms.inactive') }}
                                                </x-admin.badge>
                                            </td>
                                            <td>
                                                <x-admin.group-btn>
                                                    <x-admin.btn variant="outline-primary"
                                                                 size="sm"
                                                                 data-cms-action="order-item"
                                                                 :data-cms-id="$item->id">
                                                        {{ __('admin.cms.update_order') }}
                                                    </x-admin.btn>
                                                    <x-admin.btn variant="outline-warning"
                                                                 size="sm"
                                                                 data-cms-action="toggle-item"
                                                                 :data-cms-id="$item->id">
                                                        {{ __('admin.cms.toggle') }}
                                                    </x-admin.btn>
                                                    <x-admin.btn variant="outline-danger"
                                                                 size="sm"
                                                                 data-cms-action="delete-item"
                                                                 :data-cms-id="$item->id">
                                                        {{ __('admin.cms.delete') }}
                                                    </x-admin.btn>
                                                </x-admin.group-btn>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ __('admin.cms.no_items') }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body">
                    <div class="admin-empty-state">
                        <i class="fe fe-layers" aria-hidden="true"></i>
                        <span>{{ __('admin.cms.no_items') }}</span>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/cms-admin.js') }}"></script>
@endpush
