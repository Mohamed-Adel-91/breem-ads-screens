/**
 * Enhanced File Uploader Component
 * Supports images, videos, and files with modern Bootstrap styling and fslightbox integration
 */

$(document).ready(function() {
    // Initialize all image uploaders
    initImageUploaders();
    
    // Handle dynamic content
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('image-uploader-wrapper')) {
            initImageUploaders();
        }
    });
});

function initImageUploaders() {
    $('.modern-file-input').each(function() {
        const $input = $(this);
        const $wrapper = $input.closest('.modern-image-uploader');
        const $previewName = $wrapper.find('[id$="_name"]');
        const $previewWrapper = $wrapper.find('[id$="_wrapper"]');
        const $previewElement = $wrapper.find('[id^="preview_"]');
        const $previewLink = $wrapper.find('[id$="_link"]');
        
        if ($input.data('initialized')) return;
        $input.data('initialized', true);
        
        // Handle file selection
        $input.on('change', function() {
            handleFileSelection($(this), $previewName, $previewWrapper, $previewElement, $previewLink);
        });
        
        // Initialize existing preview
        const hasExistingFile = $input.data('has-existing-file') === 'true';
        if (hasExistingFile && $previewElement.length) {
            $previewName.text('اختر ملف آخر');
            $previewWrapper.removeClass('d-none');
        }
    });
}

function handleFileSelection($input, $previewName, $previewWrapper, $previewElement, $previewLink) {
    if (!$input[0].files || !$input[0].files[0]) {
        $previewName.text('لم يتم اختيار ملف');
        $previewWrapper.addClass('d-none');
        return;
    }
    
    const file = $input[0].files[0];
    const isImage = $input.data('is-image') === 'true';
    const isVideo = $input.data('is-video') === 'true';
    
    // Validate file type
    let isValidType = false;
    if (isImage) {
        isValidType = file.type.startsWith('image/');
    } else if (isVideo) {
        isValidType = file.type.startsWith('video/');
    } else {
        // For general files, accept most common types
        isValidType = file.type.startsWith('image/') || 
                     file.type.startsWith('video/') || 
                     file.type.startsWith('application/') || 
                     file.type.startsWith('text/');
    }
    
    if (!isValidType) {
        showToast('يرجى اختيار ملف صالح', 'error');
        $input.val('');
        return;
    }
    
    // Validate file size. A data-max-kb attribute (KB) wins when present; otherwise
    // fall back to legacy defaults. Videos must never be capped at a tiny image limit.
    const dataMaxKb = parseInt($input.data('max-kb'), 10);
    const maxSize = dataMaxKb > 0
        ? dataMaxKb * 1024
        : (isVideo ? 100 * 1024 * 1024 : 5 * 1024 * 1024);
    if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024));
        showToast(`حجم الملف يجب أن لا يتجاوز ${maxSizeMB} ميجابايت`, 'error');
        $input.val('');
        return;
    }
    
    // Update preview
    $previewName.text(file.name);
    $previewWrapper.removeClass('d-none');
    
    if (isImage) {
        // For images, create preview
        const url = URL.createObjectURL(file);
        $previewElement.attr('src', url);
        $previewLink.attr('href', url);
        
        $previewElement.on('load', function() {
            URL.revokeObjectURL(url);
            refreshFsLightbox();
        }).on('error', function() {
            URL.revokeObjectURL(url);
            showToast('فشل تحميل الصورة', 'error');
        });
    } else if (isVideo) {
        // For videos, just show the video icon
        $previewLink.attr('href', URL.createObjectURL(file));
    } else {
        // For other files, just show the file icon
        $previewLink.attr('href', URL.createObjectURL(file));
    }
}

/**
 * Dedicated CMS video uploader (schema "video" fields).
 *
 * Renders a live <video controls> preview from the chosen file, updates the
 * "Open video" link and file-name label, and enforces the per-input data-max-kb
 * limit and video/* mime type. Delegated so it also covers cloned repeater rows.
 */
$(document).on('change', 'input[data-cms-video-input="true"]', function () {
    const input = this;
    const $input = $(input);
    const file = input.files && input.files[0] ? input.files[0] : null;

    const $preview = $('#' + $input.data('preview-target'));
    const $wrap = $('#' + $input.data('preview-wrap'));
    const $open = $('#' + $input.data('open-target'));
    const $name = $('#' + $input.data('name-target'));

    if (!file) {
        $name.val('No file selected');
        return;
    }

    // Type must be a video.
    if (file.type && file.type.indexOf('video/') !== 0) {
        showToast('يرجى اختيار ملف فيديو صالح (MP4, WEBM, MOV)', 'error');
        $input.val('');
        return;
    }

    // Size limit (KB) from data-max-kb.
    const maxKb = parseInt($input.data('max-kb'), 10);
    if (maxKb > 0 && file.size > maxKb * 1024) {
        const maxMb = Math.round(maxKb / 1024);
        showToast(`حجم الفيديو يجب أن لا يتجاوز ${maxMb} ميجابايت`, 'error');
        $input.val('');
        return;
    }

    const url = URL.createObjectURL(file);
    $name.val(file.name);
    $wrap.removeClass('d-none');
    if ($preview.length) {
        $preview.attr('src', url);
        if ($preview[0] && typeof $preview[0].load === 'function') {
            $preview[0].load();
        }
    }
    if ($open.length) {
        $open.attr('href', url).removeClass('d-none');
    }
});

// Global refresh function for fslightbox
window.refreshFsLightbox = function() {
    if (typeof fsLightbox !== 'undefined' && fsLightboxInstances['default']) {
        fsLightboxInstances['default'].refresh();
    }
};
