{{--
    Static, dependency-free replacement for the legacy bootstrap-multiselect control.

    Renders an accessible fieldset of native checkboxes, optionally clustered by the
    prefix that precedes `separator` in each option label (e.g. `ads.view` -> `ads`).
    Submitted field names and values are identical to the previous <select multiple>,
    so no controller or validation change is required.
--}}

@props([
    'name',
    'options' => [],
    'selected' => [],
    'legend' => null,
    'helpText' => null,
    'groupBy' => false,
    'separator' => '.',
    'columns' => 3,
    'emptyLabel' => null,
])

@php
    $fieldName = $name;
    $inputName = $fieldName . '[]';
    $idPrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $fieldName);

    $selectedValues = collect($selected)->map(fn ($value) => (string) $value)->all();

    $items = collect($options)->map(fn ($label, $value) => [
        'value' => (string) $value,
        'label' => (string) $label,
    ])->values();

    $groups = $groupBy
        ? $items->groupBy(fn (array $item) => str_contains($item['label'], $separator)
            ? \Illuminate\Support\Str::before($item['label'], $separator)
            : '__ungrouped__')
        : collect(['__ungrouped__' => $items]);

    $groups = $groups->sortKeys();

    $hasError = $errors->has($fieldName) || $errors->has($fieldName . '.*');
    $columnClass = 'col-sm-6 col-lg-' . max(1, (int) (12 / max(1, (int) $columns)));
@endphp

<fieldset {{ $attributes->class(['admin-checkbox-group', 'mb-0']) }}>
    @if ($legend)
        <legend class="col-form-label pt-0 h6 mb-1">{{ $legend }}</legend>
    @endif

    @if ($helpText)
        <p class="text-muted small mb-2">{{ $helpText }}</p>
    @endif

    @if ($items->isEmpty())
        <p class="text-muted small mb-0">{{ $emptyLabel ?: __('admin.forms.no_options') }}</p>
    @else
        <div class="admin-checkbox-group-body" data-checkbox-group>
            @foreach ($groups as $groupKey => $groupItems)
                @php
                    $isUngrouped = $groupKey === '__ungrouped__';
                    $groupId = $idPrefix . '_group_' . \Illuminate\Support\Str::slug((string) $groupKey, '_');
                    $groupTitle = $isUngrouped ? null : \Illuminate\Support\Str::headline((string) $groupKey);
                @endphp

                <div @class(['admin-checkbox-cluster', 'mb-3' => !$loop->last]) data-checkbox-cluster>
                    @if ($groupTitle)
                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                            <span class="admin-checkbox-cluster-title" id="{{ $groupId }}_title">
                                {{ $groupTitle }}
                            </span>
                            {{-- Progressive enhancement: hidden unless the helper script runs. --}}
                            <button type="button"
                                    class="btn btn-link btn-sm p-0 admin-checkbox-toggle d-none"
                                    data-checkbox-toggle
                                    data-select-label="{{ __('admin.buttons.select_all') }}"
                                    data-clear-label="{{ __('admin.buttons.clear_all') }}"
                                    aria-describedby="{{ $groupId }}_title">
                                {{ __('admin.buttons.select_all') }}
                            </button>
                        </div>
                    @endif

                    <div class="row">
                        @foreach ($groupItems as $item)
                            @php
                                $optionId = $idPrefix . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $item['value']);
                                $optionLabel = $groupTitle
                                    ? \Illuminate\Support\Str::after($item['label'], $separator)
                                    : $item['label'];
                            @endphp
                            <div class="{{ $columnClass }}">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="{{ $optionId }}"
                                           name="{{ $inputName }}"
                                           value="{{ $item['value'] }}"
                                           @checked(in_array($item['value'], $selectedValues, true))>
                                    <label class="custom-control-label" for="{{ $optionId }}">
                                        {{ $optionLabel }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block">
            {{ $errors->first($fieldName) ?: $errors->first($fieldName . '.*') }}
        </div>
    @endif
</fieldset>

@once
    @push('scripts')
        <script>
            // Progressive enhancement only: every checkbox remains usable without this script.
            (function () {
                'use strict';

                function clusterBoxes(cluster) {
                    return Array.prototype.slice.call(cluster.querySelectorAll('input[type="checkbox"]'));
                }

                function syncToggle(cluster) {
                    var toggle = cluster.querySelector('[data-checkbox-toggle]');
                    if (!toggle) {
                        return;
                    }

                    var boxes = clusterBoxes(cluster);
                    var allChecked = boxes.length > 0 && boxes.every(function (box) {
                        return box.checked;
                    });

                    toggle.textContent = allChecked
                        ? toggle.getAttribute('data-clear-label')
                        : toggle.getAttribute('data-select-label');
                    toggle.setAttribute('aria-pressed', allChecked ? 'true' : 'false');
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var clusters = document.querySelectorAll('[data-checkbox-group] [data-checkbox-cluster]');

                    Array.prototype.forEach.call(clusters, function (cluster) {
                        var toggle = cluster.querySelector('[data-checkbox-toggle]');
                        if (!toggle) {
                            return;
                        }

                        toggle.classList.remove('d-none');
                        syncToggle(cluster);

                        toggle.addEventListener('click', function () {
                            var boxes = clusterBoxes(cluster);
                            var shouldCheck = !boxes.every(function (box) {
                                return box.checked;
                            });

                            boxes.forEach(function (box) {
                                box.checked = shouldCheck;
                            });

                            syncToggle(cluster);
                        });

                        cluster.addEventListener('change', function (event) {
                            if (event.target && event.target.type === 'checkbox') {
                                syncToggle(cluster);
                            }
                        });
                    });
                });
            })();
        </script>
    @endpush
@endonce
