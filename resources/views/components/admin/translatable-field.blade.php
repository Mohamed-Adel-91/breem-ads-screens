@props([
    // Exact input names, keyed by locale. They are emitted verbatim so the
    // existing backend contract is preserved.
    'names' => [],
    // Pre-resolved values (call sites keep their own old() expressions).
    'values' => [],
    // Optional dot-notation validation keys; derived from the names otherwise.
    'errorKeys' => [],
    // Translation key containing a :locale placeholder, e.g. admin.cms.shared.title_locale
    'labelKey' => null,
    // Extra replacements for label keys that carry more than :locale.
    'labelReplace' => [],
    // Plain label used when no labelKey is given.
    'label' => null,
    'type' => 'text',
    'rows' => 3,
    'help' => null,
    'idPrefix' => null,
])

@php
    // English first, then Arabic: an admin working in either dashboard locale
    // edits both languages in the same, predictable order.
    $localeMeta = [
        'en' => ['dir' => 'ltr', 'name' => __('admin.cms.ui.english')],
        'ar' => ['dir' => 'rtl', 'name' => __('admin.cms.ui.arabic')],
    ];

    $renderable = array_filter(
        $localeMeta,
        fn ($locale) => filled($names[$locale] ?? null),
        ARRAY_FILTER_USE_KEY,
    );

    $prefix = $idPrefix ?: 'tf_' . \Illuminate\Support\Str::random(6);
@endphp

@if (!empty($renderable))
    <div class="form-row">
        @foreach ($renderable as $locale => $meta)
            @php
                $inputName = $names[$locale];
                $errorKey = $errorKeys[$locale]
                    ?? str_replace(['][', '[', ']'], ['.', '.', ''], $inputName);
                $hasError = $errors->has($errorKey);
                $fieldId = $prefix . '_' . $locale;
                $fieldLabel = $labelKey
                    ? __($labelKey, array_merge($labelReplace, ['locale' => $meta['name']]))
                    : trim(($label ?? '') . ' (' . $meta['name'] . ')');
                $value = $values[$locale] ?? '';
            @endphp

            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <label for="{{ $fieldId }}" lang="{{ $locale }}">{{ $fieldLabel }}</label>

                    @if ($type === 'textarea')
                        <textarea id="{{ $fieldId }}"
                                  name="{{ $inputName }}"
                                  rows="{{ $rows }}"
                                  dir="{{ $meta['dir'] }}"
                                  lang="{{ $locale }}"
                                  @if ($help) aria-describedby="{{ $fieldId }}_help" @endif
                                  @class(['form-control', 'is-invalid' => $hasError])>{{ $value }}</textarea>
                    @else
                        <input type="{{ $type }}"
                               id="{{ $fieldId }}"
                               name="{{ $inputName }}"
                               value="{{ $value }}"
                               dir="{{ $meta['dir'] }}"
                               lang="{{ $locale }}"
                               @if ($help) aria-describedby="{{ $fieldId }}_help" @endif
                               @class(['form-control', 'is-invalid' => $hasError])>
                    @endif

                    @error($errorKey)
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    @if ($help)
                        <small id="{{ $fieldId }}_help" class="form-text text-muted">{{ $help }}</small>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
