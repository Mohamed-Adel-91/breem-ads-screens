<div class="{{ $class ?? 'form-group col-6' }}">
    @php $fieldId = $inputId ?? $name; @endphp
    <label for="{{ $fieldId }}">{{ $label }}</label>
    @isset($sizeHint)
        <span class="text-muted">{{ $sizeHint }}</span>
    @endisset
    <input type="file" class="form-control-file" id="{{ $fieldId }}" name="{{ $name }}"
        accept="{{ $acceptedTypes ?? 'image/*,video/mp4' }}" {{ $required ?? '' }}>
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
                    Your browser does not support the video tag.
                </video>
            @elseif ($isPdfFile)
                <a href="{{ asset($previewPath) }}" target="_blank" class="btn btn-outline-primary">
                    View File
                </a>
            @else
                <img src="{{ asset($previewPath) }}" alt="{{ $label }} Preview" class="img-thumbnail"
                    style="max-height: 200px; width: auto;">
            @endif
        @else
            <p>No media selected</p>
            <img src="" class="img-thumbnail" style="max-height: 200px; width: auto; display: none;">
            <video controls class="img-thumbnail" style="max-height: 200px; width: auto; display: none;">
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <a href="" target="_blank" class="btn btn-outline-primary pdf-preview" style="display: none;">View File</a>
        @endif
    </div>
</div>
@push('custom-js-scripts')
    <script>
        document.getElementById("{{ $fieldId }}").addEventListener("change", function(event) {
            var file = event.target.files[0];
            var previewContainer = document.getElementById("preview_{{ $fieldId }}");
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        previewContainer.querySelector("img").src = e.target.result;
                        previewContainer.querySelector("img").style.display = "block";
                        previewContainer.querySelector("video").style.display = "none";
                        previewContainer.querySelector(".pdf-preview").style.display = "none";
                    } else if (file.type === "video/mp4") {
                        previewContainer.querySelector("video source").src = e.target.result;
                        previewContainer.querySelector("video").style.display = "block";
                        previewContainer.querySelector("img").style.display = "none";
                        previewContainer.querySelector(".pdf-preview").style.display = "none";
                        previewContainer.querySelector("video").load();
                    } else if (file.type === "application/pdf") {
                        var link = previewContainer.querySelector(".pdf-preview");
                        link.href = e.target.result;
                        link.style.display = "inline-block";
                        previewContainer.querySelector("img").style.display = "none";
                        previewContainer.querySelector("video").style.display = "none";
                    }
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.querySelector("img").style.display = "none";
                previewContainer.querySelector("video").style.display = "none";
                if (previewContainer.querySelector(".pdf-preview")) {
                    previewContainer.querySelector(".pdf-preview").style.display = "none";
                }
            }
        });
    </script>
@endpush
