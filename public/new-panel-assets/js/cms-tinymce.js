(function () {
    'use strict';

    var defaultUploadFolder = 'uploads/cms/tinymce';

    function config() {
        return window.cmsTinyMceConfig || {};
    }

    function debugEnabled() {
        var cfg = config();

        return Boolean((cfg.debug || window.cmsPanelDebug) && window.console);
    }

    function isInsideTemplate(element) {
        return Boolean(element && element.closest && element.closest('template'));
    }

    function isInsideHiddenContainer(element) {
        if (!element || !element.closest) {
            return false;
        }

        if (element.closest('.d-none')) {
            return true;
        }

        var collapse = element.closest('.collapse');

        return Boolean(collapse && !collapse.classList.contains('show'));
    }

    function isVisible(element) {
        return Boolean(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }

    function ensureId(textarea) {
        if (textarea.id) {
            return textarea.id;
        }

        textarea.id = 'cms-tinymce-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);

        return textarea.id;
    }

    function findElements(scope, selector) {
        if (!scope) {
            scope = document;
        }

        if (scope.matches && scope.matches(selector)) {
            return [scope];
        }

        return scope.querySelectorAll(selector);
    }

    window.cmsLoadLazyImages = function (container) {
        var debug = debugEnabled();
        var label = 'cms-lazy-images';
        var counts = {
            total: 0,
            loaded: 0,
            skippedTemplate: 0,
            skippedHidden: 0,
            skippedExisting: 0,
            missingSrc: 0
        };

        if (debug && typeof window.console.time === 'function') {
            window.console.time(label);
        }

        Array.prototype.forEach.call(findElements(container || document, 'img.cms-lazy-preview'), function (image) {
            counts.total++;

            if (isInsideTemplate(image)) {
                counts.skippedTemplate++;
                return;
            }

            if (isInsideHiddenContainer(image)) {
                counts.skippedHidden++;
                return;
            }

            if (image.dataset.lazyLoaded === '1') {
                counts.skippedExisting++;
                return;
            }

            var src = image.dataset.src;

            if (!src) {
                counts.missingSrc++;
                return;
            }

            image.src = src;
            image.dataset.lazyLoaded = '1';
            counts.loaded++;
        });

        if (debug) {
            if (typeof window.console.timeEnd === 'function') {
                window.console.timeEnd(label);
            }

            if (typeof window.console.info === 'function') {
                window.console.info('cms-lazy-images counts', counts);
            }
        }
    };

    function uploadImage(textarea, blobInfo, progress) {
        var formData = new FormData();
        var cfg = config();

        formData.append('file', blobInfo.blob(), blobInfo.filename());
        formData.append('folder', textarea.dataset.uploadFolder || defaultUploadFolder);

        return fetch(cfg.uploadUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        }).then(function (response) {
            if (typeof progress === 'function') {
                progress(100);
            }

            return response.json().catch(function () {
                return {};
            }).then(function (json) {
                if (!response.ok) {
                    throw new Error(json.error || json.message || 'Image upload failed.');
                }

                if (!json.location) {
                    throw new Error('Image upload failed: missing image URL.');
                }

                return json.location;
            });
        });
    }

    function initTextarea(textarea) {
        var cfg = config();
        var id = ensureId(textarea);

        if (textarea.dataset.tinymceInitializing === '1' || textarea.dataset.tinymceInitialized === '1') {
            return false;
        }

        if (window.tinymce.get(id)) {
            textarea.dataset.tinymceInitialized = '1';
            return false;
        }

        textarea.dataset.tinymceInitializing = '1';

        var initResult = window.tinymce.init({
            target: textarea,
            base_url: cfg.baseUrl,
            suffix: '.min',
            license_key: 'gpl',
            height: 300,
            menubar: false,
            branding: false,
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            automatic_uploads: true,
            file_picker_types: 'image',
            plugins: 'lists link image table code autoresize directionality',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | ltr rtl | code',
            directionality: textarea.getAttribute('dir') === 'ltr' ? 'ltr' : 'rtl',
            images_upload_handler: function (blobInfo, progress) {
                return uploadImage(textarea, blobInfo, progress);
            },
            setup: function (editor) {
                editor.on('init', function () {
                    delete textarea.dataset.tinymceInitializing;
                    textarea.dataset.tinymceInitialized = '1';
                });

                editor.on('remove', function () {
                    delete textarea.dataset.tinymceInitializing;
                    delete textarea.dataset.tinymceInitialized;
                });
            }
        });

        if (initResult && typeof initResult.catch === 'function') {
            initResult.catch(function (error) {
                delete textarea.dataset.tinymceInitializing;
                delete textarea.dataset.tinymceInitialized;

                if (window.console && typeof window.console.error === 'function') {
                    window.console.error('TinyMCE initialization failed', error);
                }
            });
        }

        return true;
    }

    window.cmsInitTinyMCE = function (container) {
        var debug = debugEnabled();
        var label = 'cms-tinymce-init';
        var counts = {
            total: 0,
            initialized: 0,
            skippedTemplate: 0,
            skippedExisting: 0,
            skippedHidden: 0,
            skippedDisabledReadonly: 0
        };

        if (!window.tinymce) {
            return;
        }

        if (debug && typeof window.console.time === 'function') {
            window.console.time(label);
        }

        var scope = container || document;
        var textareas = findElements(scope, 'textarea.cms-tinymce[data-tinymce="true"]');

        Array.prototype.forEach.call(textareas, function (textarea) {
            counts.total++;

            if (isInsideTemplate(textarea)) {
                counts.skippedTemplate++;
                return;
            }

            if (textarea.disabled || textarea.readOnly) {
                counts.skippedDisabledReadonly++;
                return;
            }

            if (textarea.dataset.tinymceInitializing === '1'
                || textarea.dataset.tinymceInitialized === '1'
                || (textarea.id && window.tinymce.get(textarea.id))) {
                counts.skippedExisting++;
                return;
            }

            if (isInsideHiddenContainer(textarea) || !isVisible(textarea)) {
                counts.skippedHidden++;
                return;
            }

            if (initTextarea(textarea)) {
                counts.initialized++;
            }
        });

        if (debug) {
            if (typeof window.console.timeEnd === 'function') {
                window.console.timeEnd(label);
            }

            if (typeof window.console.info === 'function') {
                window.console.info('cms-tinymce-init counts', counts);
            }
        }
    };

    window.cmsRemoveTinyMCE = function (container) {
        if (!window.tinymce || !container) {
            return;
        }

        var textareas = container.matches && container.matches('textarea.cms-tinymce')
            ? [container]
            : container.querySelectorAll('textarea.cms-tinymce');

        Array.prototype.forEach.call(textareas, function (textarea) {
            if (textarea.id && window.tinymce.get(textarea.id)) {
                window.tinymce.get(textarea.id).remove();
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.cmsLoadLazyImages(document);
        window.cmsInitTinyMCE(document);
    });

    document.addEventListener('submit', function () {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }
    }, true);
})();
