@props([
    'name',
    'label' => null,
    'inputId' => null,
    'previewPath' => null,
    'kind' => 'auto',
    'accept' => null,
    'acceptedTypesHint' => null,
    'maxSizeHint' => null,
    'helpText' => null,
    'required' => false,
    'errorKey' => null,
])

@php
    // The path is stored by the CMS controllers; it is turned into a public URL
    // exactly the way the legacy media-upload partial did (asset() on the raw path).
    $currentUrl = filled($previewPath) ? asset($previewPath) : null;

    $extension = $currentUrl
        ? \Illuminate\Support\Str::lower(pathinfo(parse_url($previewPath, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION))
        : '';

    $resolvedKind = $kind;
    if ($resolvedKind === 'auto') {
        $resolvedKind = match (true) {
            in_array($extension, ['mp4', 'webm', 'ogg'], true) => 'video',
            $extension === 'pdf' => 'file',
            $extension !== '' => 'image',
            default => 'file',
        };
    }

    $fieldId = $inputId ?: 'upload_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
    $fieldLabel = $label ?: __('admin.forms.image');

    // Validation errors use dot notation while the input name uses brackets.
    $resolvedErrorKey = $errorKey ?: str_replace(['][', '[', ']'], ['.', '.', ''], $name);
    $hasError = $errors->has($resolvedErrorKey);

    $defaultAccept = match ($resolvedKind) {
        'video' => 'video/mp4',
        'image' => 'image/jpeg,image/png,image/gif,image/webp',
        default => null,
    };
    $resolvedAccept = $accept ?: $defaultAccept;

    $defaultTypesHint = match ($resolvedKind) {
        'video' => 'MP4',
        'image' => 'JPG, PNG, GIF, WEBP',
        default => null,
    };
    $resolvedTypesHint = $acceptedTypesHint ?: $defaultTypesHint;

    $describedBy = collect([
        $helpText ? $fieldId . '_help' : null,
        $fieldId . '_hints',
    ])->filter()->implode(' ');
@endphp

<div class="form-group" data-file-uploader-field>
    <label for="{{ $fieldId }}">
        {{ $fieldLabel }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="mb-2" data-file-uploader-preview>
        @if ($currentUrl && $resolvedKind === 'image')
            <x-admin.media-preview
                :url="$currentUrl"
                :alt="__('admin.media.preview_alt', ['label' => $fieldLabel])"
                :caption="__('admin.media.current_image')" />
        @elseif ($currentUrl && $resolvedKind === 'video')
            <span class="d-block text-muted small mb-1">{{ __('admin.cms.ui.video_preview') }}</span>
            <video controls preload="metadata" class="img-thumbnail" style="max-height: 180px; width: auto;">
                <source src="{{ $currentUrl }}" type="video/mp4">
                {{ __('admin.cms.ui.unsupported_video') }}
            </video>
        @elseif ($currentUrl)
            <span class="d-block text-muted small mb-1">{{ __('admin.cms.ui.current_file') }}</span>
            <a href="{{ $currentUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-sm btn-outline-primary"
               aria-label="{{ __('admin.cms.ui.open_current_file') }}">
                <i class="fe fe-external-link" aria-hidden="true"></i>
                <span>{{ __('admin.cms.ui.open_current_file') }}</span>
            </a>
        @else
            <p class="text-muted small mb-0">{{ __('admin.cms.ui.no_file_selected') }}</p>
        @endif
    </div>

    <input type="file"
           id="{{ $fieldId }}"
           name="{{ $name }}"
           @if ($resolvedAccept) accept="{{ $resolvedAccept }}" @endif
           data-file-uploader
           data-preview-alt="{{ __('admin.media.preview_alt', ['label' => $fieldLabel]) }}"
           aria-describedby="{{ $describedBy }}"
           @if ($required) required @endif
           @class(['form-control-file', 'is-invalid' => $hasError])>

    @error($resolvedErrorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if ($helpText)
        <small id="{{ $fieldId }}_help" class="form-text text-muted">{{ $helpText }}</small>
    @endif

    <small id="{{ $fieldId }}_hints" class="form-text text-muted">
        @if ($resolvedTypesHint)
            {{ __('admin.media.accepted_types', ['types' => $resolvedTypesHint]) }}
        @endif
        @if ($maxSizeHint)
            &middot; {{ __('admin.media.max_size', ['size' => $maxSizeHint]) }}
        @endif
        @if ($currentUrl)
            &middot; {{ __('admin.cms.ui.keep_current_file_hint') }}
        @endif
    </small>
</div>
