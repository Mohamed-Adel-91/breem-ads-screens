@props([
    'name',
    'label' => null,
    'inputId' => null,
    'previewUrl' => null,
    'oldFile' => null,
    'accept' => 'image/jpeg,image/png,image/gif,image/webp',
    'acceptedTypesHint' => 'JPG, PNG, GIF, WEBP',
    'maxSizeHint' => '5 MB',
    'sizeHint' => null,
    'required' => false,
    'helpText' => null,
    'keepCurrentHint' => true,
])

@php
    $fieldId = $inputId ?: 'upload_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
    $fieldLabel = $label ?: __('admin.table.image');
    $hasError = $errors->has($name);
    $describedBy = collect([
        $helpText ? $fieldId . '_help' : null,
        $fieldId . '_types',
    ])->filter()->implode(' ');
@endphp

<div class="form-group" data-image-uploader-field>
    <label for="{{ $fieldId }}">
        {{ $fieldLabel }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
        @if ($sizeHint)
            <span class="text-muted small">{{ $sizeHint }}</span>
        @endif
    </label>

    <div class="mb-2" data-image-uploader-preview-wrap>
        <x-admin.media-preview
            :url="$previewUrl"
            :alt="__('admin.media.preview_alt', ['label' => $fieldLabel])"
            :caption="filled($previewUrl) ? __('admin.media.current_image') : null" />
    </div>

    <input type="file"
           id="{{ $fieldId }}"
           name="{{ $name }}"
           accept="{{ $accept }}"
           data-image-uploader
           data-preview-alt="{{ __('admin.media.preview_alt', ['label' => $fieldLabel]) }}"
           aria-describedby="{{ $describedBy }}"
           @if ($required) required @endif
           @class(['form-control-file', 'is-invalid' => $hasError])>

    @if (filled($oldFile))
        <input type="hidden" name="old_{{ $name }}" value="{{ $oldFile }}">
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if ($helpText)
        <small id="{{ $fieldId }}_help" class="form-text text-muted">{{ $helpText }}</small>
    @endif

    <small id="{{ $fieldId }}_types" class="form-text text-muted">
        {{ __('admin.media.accepted_types', ['types' => $acceptedTypesHint]) }}
        &middot;
        {{ __('admin.media.max_size', ['size' => $maxSizeHint]) }}
        @if ($keepCurrentHint && filled($previewUrl))
            &middot; {{ __('admin.media.keep_current_hint') }}
        @endif
    </small>
</div>

@once
    @push('scripts')
        <script>
            // Progressive enhancement only: the plain file input stays fully functional
            // when JavaScript is unavailable or fails.
            (function () {
                'use strict';

                function previewContainer(input) {
                    var field = input.closest('[data-image-uploader-field]');

                    return field ? field.querySelector('[data-image-uploader-preview-wrap]') : null;
                }

                function renderPreview(container, dataUrl, altText) {
                    var image = document.createElement('img');
                    image.className = 'img-thumbnail';
                    image.style.maxHeight = '150px';
                    image.style.width = 'auto';
                    image.alt = altText;
                    image.src = dataUrl;

                    // The stored image is replaced by the freshly picked one, so neither the
                    // "current image" caption nor the link to the persisted file still applies.
                    var caption = container.querySelector('[data-media-preview-caption]');
                    if (caption) {
                        caption.remove();
                    }

                    var existing = container.querySelector('a') || container.querySelector('img') || container.querySelector('p');

                    if (existing) {
                        existing.replaceWith(image);
                    } else {
                        container.appendChild(image);
                    }
                }

                document.addEventListener('change', function (event) {
                    var input = event.target;

                    if (!input || input.type !== 'file' || !input.hasAttribute('data-image-uploader')) {
                        return;
                    }

                    var container = previewContainer(input);
                    var file = input.files && input.files[0];

                    if (!container || !file || file.type.indexOf('image/') !== 0) {
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        renderPreview(container, loadEvent.target.result, input.getAttribute('data-preview-alt') || '');
                    };
                    reader.readAsDataURL(file);
                });
            })();
        </script>
    @endpush
@endonce
