/**
 * Breem admin — CMS behaviour.
 *
 * Static, build-free vanilla JavaScript loaded only by the CMS screens.
 * Everything here is progressive enhancement on top of markup that already
 * works without JavaScript, except the generic page editor, whose AJAX
 * contract is inherited unchanged from the legacy implementation.
 */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.getAttribute('content') : '';
    }

    /* ------------------------------------------------------------------ */
    /* Repeaters                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Markup contract:
     *   <button data-repeater-add="partners">
     *   <div   data-repeater-items="partners">
     *   <template data-repeater-template="partners">  … __INDEX__ …
     *   <button data-repeater-remove> inside a [data-repeater-item]
     *
     * New rows get a timestamp index so they never collide with the indexes
     * already rendered server-side.
     */
    function initRepeaters() {
        document.addEventListener('click', function (event) {
            var addButton = event.target.closest('[data-repeater-add]');

            if (addButton) {
                event.preventDefault();
                addRepeaterItem(addButton.getAttribute('data-repeater-add'));

                return;
            }

            var removeButton = event.target.closest('[data-repeater-remove]');

            if (!removeButton) {
                return;
            }

            event.preventDefault();

            var message = removeButton.getAttribute('data-repeater-confirm');

            if (message && !window.confirm(message)) {
                return;
            }

            var item = removeButton.closest('[data-repeater-item]');

            if (item) {
                var container = item.parentElement;
                item.remove();
                refreshRepeaterEmptyState(container);
            }
        });
    }

    function addRepeaterItem(key) {
        if (!key) {
            return;
        }

        var template = document.querySelector('[data-repeater-template="' + key + '"]');
        var container = document.querySelector('[data-repeater-items="' + key + '"]');

        if (!template || !container) {
            return;
        }

        var markup = template.innerHTML.replace(/__INDEX__/g, String(Date.now()));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = markup.trim();

        var element = wrapper.firstElementChild;

        if (!element) {
            return;
        }

        container.appendChild(element);
        refreshRepeaterEmptyState(container);

        var firstField = element.querySelector('input:not([type="hidden"]), textarea, select');

        if (firstField) {
            firstField.focus();
        }
    }

    function refreshRepeaterEmptyState(container) {
        if (!container) {
            return;
        }

        var placeholder = container.querySelector('[data-repeater-empty]');

        if (!placeholder) {
            return;
        }

        var hasItems = container.querySelector('[data-repeater-item]') !== null;
        placeholder.hidden = hasItems;
    }

    /* ------------------------------------------------------------------ */
    /* File uploader preview                                               */
    /* ------------------------------------------------------------------ */

    function initFilePreviews() {
        document.addEventListener('change', function (event) {
            var input = event.target;

            if (!input || input.type !== 'file' || !input.hasAttribute('data-file-uploader')) {
                return;
            }

            var field = input.closest('[data-file-uploader-field]');
            var container = field ? field.querySelector('[data-file-uploader-preview]') : null;
            var file = input.files && input.files[0];

            if (!container || !file) {
                return;
            }

            if (file.type.indexOf('image/') === 0) {
                readAsDataUrl(file, function (dataUrl) {
                    var image = document.createElement('img');
                    image.className = 'img-thumbnail';
                    image.style.maxHeight = '150px';
                    image.style.width = 'auto';
                    image.alt = input.getAttribute('data-preview-alt') || '';
                    image.src = dataUrl;
                    container.replaceChildren(image);
                });

                return;
            }

            if (file.type.indexOf('video/') === 0) {
                readAsDataUrl(file, function (dataUrl) {
                    var video = document.createElement('video');
                    video.controls = true;
                    video.className = 'img-thumbnail';
                    video.style.maxHeight = '180px';
                    video.style.width = 'auto';
                    video.src = dataUrl;
                    container.replaceChildren(video);
                });

                return;
            }

            // Anything else (PDF, generic files): show the picked file name only.
            var name = document.createElement('p');
            name.className = 'text-muted small mb-0';
            name.textContent = file.name;
            container.replaceChildren(name);
        });
    }

    function readAsDataUrl(file, callback) {
        var reader = new FileReader();
        reader.onload = function (event) {
            callback(event.target.result);
        };
        reader.readAsDataURL(file);
    }

    /* ------------------------------------------------------------------ */
    /* Generic page editor                                                 */
    /* ------------------------------------------------------------------ */

    function initPageEditor() {
        var root = document.querySelector('[data-cms-page-editor]');

        if (!root) {
            return;
        }

        var messages = parseJson(root.getAttribute('data-messages')) || {};
        var templates = {
            sectionToggle: root.getAttribute('data-section-toggle-url'),
            section: root.getAttribute('data-section-url'),
            itemToggle: root.getAttribute('data-item-toggle-url'),
            item: root.getAttribute('data-item-url')
        };

        function url(template, id) {
            return (template || '').replace('__ID__', encodeURIComponent(id));
        }

        function notify(type, message) {
            var region = root.querySelector('[data-cms-notifications]');

            if (!region) {
                window.alert(message);

                return;
            }

            var alert = document.createElement('div');
            alert.className = 'alert alert-' + type + ' alert-dismissible fade show rounded p-3';
            alert.setAttribute('role', 'alert');
            alert.textContent = message;

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'close';
            close.setAttribute('data-dismiss', 'alert');
            close.setAttribute('aria-label', messages.close || 'Close');
            close.innerHTML = '<span aria-hidden="true">&times;</span>';
            alert.appendChild(close);

            region.replaceChildren(alert);
            region.scrollIntoView({ block: 'nearest' });
        }

        /**
         * Routes and CSRF are unchanged. Multipart bodies are sent as POST with
         * Laravel method spoofing, because PHP only parses multipart form data
         * for POST — a real PATCH would deliver an empty $_POST and $_FILES.
         * Content-Type is deliberately left unset so the browser can generate
         * the multipart boundary.
         */
        function send(method, endpoint, body) {
            var headers = {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            };

            var verb = method;
            var payload;

            if (body instanceof FormData) {
                body.append('_method', method);
                verb = 'POST';
                payload = body;
            } else if (body) {
                headers['Content-Type'] = 'application/json';
                payload = JSON.stringify(body);
            }

            return window.fetch(endpoint, {
                method: verb,
                headers: headers,
                credentials: 'same-origin',
                body: payload
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.status === 204 ? {} : response.json();
            });
        }

        function applyActiveBadge(badge, isActive) {
            if (!badge) {
                return;
            }

            badge.classList.remove('badge-success', 'badge-danger');
            badge.classList.add(isActive ? 'badge-success' : 'badge-danger');
            badge.textContent = isActive ? (messages.active || '') : (messages.inactive || '');
        }

        function promptOrder() {
            var raw = window.prompt(messages.orderPrompt || '', '0');

            if (raw === null) {
                return null;
            }

            var parsed = parseInt(raw, 10);

            if (isNaN(parsed) || parsed < 0) {
                notify('danger', messages.invalidOrder || '');

                return null;
            }

            return parsed;
        }

        root.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-cms-action]');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            var action = trigger.getAttribute('data-cms-action');
            var id = trigger.getAttribute('data-cms-id');

            if (action === 'toggle-section') {
                send('PATCH', url(templates.sectionToggle, id))
                    .then(function (data) {
                        var card = document.getElementById('section_' + id);
                        applyActiveBadge(card && card.querySelector('[data-section-active]'), data.is_active);
                    })
                    .catch(function () {
                        notify('danger', messages.sectionToggleFailed || '');
                    });

                return;
            }

            if (action === 'toggle-item') {
                send('PATCH', url(templates.itemToggle, id))
                    .then(function (data) {
                        var row = document.getElementById('item_' + id);
                        applyActiveBadge(row && row.querySelector('[data-item-active]'), data.is_active);
                    })
                    .catch(function () {
                        notify('danger', messages.itemToggleFailed || '');
                    });

                return;
            }

            if (action === 'order-section' || action === 'order-item') {
                var order = promptOrder();

                if (order === null) {
                    return;
                }

                var isSection = action === 'order-section';

                send('PATCH', url(isSection ? templates.section : templates.item, id), { order: order })
                    .then(function (data) {
                        var scope = document.getElementById((isSection ? 'section_' : 'item_') + id);
                        var target = scope && scope.querySelector(isSection ? '[data-section-order]' : '[data-item-order]');
                        var value = isSection ? (data.section && data.section.order) : (data.item && data.item.order);

                        if (target) {
                            target.textContent = typeof value === 'undefined' ? order : value;
                        }

                        notify('success', messages.orderUpdated || '');
                    })
                    .catch(function () {
                        notify('danger', messages.saveFailed || '');
                    });

                return;
            }

            if (action === 'delete-section' || action === 'delete-item') {
                var isSectionDelete = action === 'delete-section';
                var confirmation = isSectionDelete ? messages.deleteSectionConfirm : messages.deleteItemConfirm;

                if (!window.confirm(confirmation || '')) {
                    return;
                }

                send('DELETE', url(isSectionDelete ? templates.section : templates.item, id))
                    .then(function () {
                        var element = document.getElementById((isSectionDelete ? 'section_' : 'item_') + id);

                        if (element) {
                            element.remove();
                        }

                        notify('success', isSectionDelete ? (messages.sectionDeleted || '') : (messages.itemDeleted || ''));
                    })
                    .catch(function () {
                        notify('danger', messages.deleteFailed || '');
                    });

                return;
            }

            if (action === 'save-section') {
                saveSectionData(id);

                return;
            }

            if (action === 'format-section') {
                var formatTarget = document.getElementById('secdata_' + id);

                if (!formatTarget) {
                    return;
                }

                try {
                    var value = formatTarget.value.trim() ? JSON.parse(formatTarget.value) : {};
                    formatTarget.value = JSON.stringify(value, null, 2);
                } catch (error) {
                    notify('danger', messages.invalidJsonFix || '');
                }

                return;
            }

            if (action === 'clear-section') {
                if (!window.confirm(messages.clearDataConfirm || '')) {
                    return;
                }

                var clearTarget = document.getElementById('secdata_' + id);

                if (clearTarget) {
                    clearTarget.value = '{}';
                    clearTarget.hidden = false;
                }
            }
        });

        function saveSectionData(id) {
            var form = document.getElementById('secform_' + id);

            if (form) {
                var body = new FormData();

                form.querySelectorAll('[data-sec-text]').forEach(function (element) {
                    body.append('section_data[' + element.getAttribute('data-key') + ']', element.value || '');
                });

                form.querySelectorAll('[data-sec-checkbox]').forEach(function (element) {
                    body.append('section_data[' + element.getAttribute('data-key') + ']', element.checked ? 1 : 0);
                });

                var currentPaths = {};
                form.querySelectorAll('[data-sec-current-value]').forEach(function (element) {
                    currentPaths[element.getAttribute('data-key')] = element.value || '';
                });

                form.querySelectorAll('input[type="file"][name^="uploads["]').forEach(function (input) {
                    var match = input.name.match(/^uploads\[(.+)\]$/);
                    var key = match ? match[1] : null;

                    if (!key) {
                        return;
                    }

                    if (input.files && input.files[0]) {
                        body.append(input.name, input.files[0]);
                    } else if (Object.prototype.hasOwnProperty.call(currentPaths, key)) {
                        body.append('section_data[' + key + ']', currentPaths[key] || '');
                    }
                });

                send('PATCH', url(templates.section, id), body)
                    .then(function () {
                        notify('success', messages.sectionDataUpdated || '');
                    })
                    .catch(function () {
                        notify('danger', messages.sectionDataSaveFailed || '');
                    });

                return;
            }

            // Fallback: the raw JSON textarea.
            var textarea = document.getElementById('secdata_' + id);

            if (!textarea) {
                return;
            }

            var parsed;

            try {
                parsed = textarea.value.trim() ? JSON.parse(textarea.value) : {};
            } catch (error) {
                notify('danger', messages.invalidJsonFix || '');

                return;
            }

            send('PATCH', url(templates.section, id), { section_data: parsed })
                .then(function () {
                    notify('success', messages.sectionDataUpdated || '');
                })
                .catch(function () {
                    notify('danger', messages.sectionDataSaveFailed || '');
                });
        }
    }

    function parseJson(value) {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return null;
        }
    }

    ready(function () {
        initRepeaters();
        initFilePreviews();
        initPageEditor();
    });
})();
