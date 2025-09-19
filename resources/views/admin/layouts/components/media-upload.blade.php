<div class="{{ $class ?? 'form-group col-6' }}">
    @php $fieldId = $inputId ?? $name; @endphp
    <label for="{{ $fieldId }}">{{ $label }}</label>
    @isset($sizeHint)
        <span class="text-muted">{{ $sizeHint }}</span>
    @endisset
    <input type="file" class="form-control-file" id="{{ $fieldId }}" name="{{ $name }}"
        accept="{{ $acceptedTypes ?? 'image/*,video/mp4' }}" {{ $required ?? '' }} data-media-upload="{{ $fieldId }}">
    @if (!empty($oldFile))
        <input type="hidden" name="old_{{ $name }}" value="{{ $oldFile }}">
    @endif
    <div id="preview_{{ $fieldId }}" class="mt-2">
        @if (!empty($previewPath))
            @php
                $isVideoFile = $isVideo ?? Str::endsWith($previewPath, '.mp4');
                $isPdfFile = Str::endsWith($previewPath, '.pdf');
            @endphp
            @if ($isVideoFile)
                <video controls class="img-thumbnail" style="max-height: 200px; width: auto;">
                    <source src="{{ asset($previewPath) }}" type="video/mp4">
                    {{ __('media_upload.unsupported_video') }}
                </video>
            @elseif ($isPdfFile)
                <a href="{{ asset($previewPath) }}" target="_blank" class="btn btn-outline-primary">
                    {{ __('media_upload.view_file') }}
                </a>
            @else
                <img src="{{ asset($previewPath) }}" alt="{{ __('media_upload.preview_alt', ['label' => $label]) }}" class="img-thumbnail"
                    style="max-height: 200px; width: auto;">
            @endif
        @else
            <p>{{ __('media_upload.no_media_selected') }}</p>
            <img src="" class="img-thumbnail" style="max-height: 200px; width: auto; display: none;">
            <video controls class="img-thumbnail" style="max-height: 200px; width: auto; display: none;">
                <source src="" type="video/mp4">
                {{ __('media_upload.unsupported_video') }}
            </video>
            <a href="" target="_blank" class="btn btn-outline-primary pdf-preview" style="display: none;">{{ __('media_upload.view_file') }}</a>
        @endif
    </div>
</div>
@push('custom-js-scripts')
    <script>
        window.registerMediaUpload = window.registerMediaUpload || function(fieldId) {
            var input = document.getElementById(fieldId);
            if (!input || input.dataset.mediaUploadBound === '1') {
                return;
            }

            input.dataset.mediaUploadBound = '1';
            input.addEventListener("change", function(event) {
                var file = event.target.files[0];
                var previewContainer = document.getElementById("preview_" + fieldId);

                if (!previewContainer) {
                    return;
                }

                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            var img = previewContainer.querySelector("img");
                            if (img) {
                                img.src = e.target.result;
                                img.style.display = "block";
                            }

                            var video = previewContainer.querySelector("video");
                            if (video) {
                                video.style.display = "none";
                            }

                            var pdf = previewContainer.querySelector(".pdf-preview");
                            if (pdf) {
                                pdf.style.display = "none";
                            }
                        } else if (file.type === "video/mp4") {
                            var video = previewContainer.querySelector("video");
                            if (video) {
                                var source = video.querySelector("source");
                                if (source) {
                                    source.src = e.target.result;
                                }
                                video.style.display = "block";
                                video.load();
                            }

                            var img = previewContainer.querySelector("img");
                            if (img) {
                                img.style.display = "none";
                            }

                            var pdf = previewContainer.querySelector(".pdf-preview");
                            if (pdf) {
                                pdf.style.display = "none";
                            }
                        } else if (file.type === "application/pdf") {
                            var link = previewContainer.querySelector(".pdf-preview");
                            if (link) {
                                link.href = e.target.result;
                                link.style.display = "inline-block";
                            }

                            var img = previewContainer.querySelector("img");
                            if (img) {
                                img.style.display = "none";
                            }

                            var video = previewContainer.querySelector("video");
                            if (video) {
                                video.style.display = "none";
                            }
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    var img = previewContainer.querySelector("img");
                    if (img) {
                        img.style.display = "none";
                    }

                    var video = previewContainer.querySelector("video");
                    if (video) {
                        video.style.display = "none";
                    }

                    var pdf = previewContainer.querySelector(".pdf-preview");
                    if (pdf) {
                        pdf.style.display = "none";
                    }
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.registerMediaUpload("{{ $fieldId }}");
        });
    </script>
@endpush
