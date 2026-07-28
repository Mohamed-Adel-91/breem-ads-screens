/**
 * ============================================================================
 * GLOBAL JAVASCRIPT FUNCTIONS
 * Dashboard Application - EVA Cakes Admin
 * ============================================================================
 */

// ============================================================================
// SECTION 1: BUTTON UTILITIES
// ============================================================================

/**
 * Disable button and show loading spinner
 * @param {jQuery} $button - Button element
 * @param {string|null} loadingText - Optional loading text
 * @returns {{originalText: string, originalDisabled: boolean}|null}
 */
function disableButtonWithSpinner($button, loadingText = null) {
    if (!$button.length) return null;

    const originalText = $button.html();
    const originalDisabled = $button.prop("disabled");
    const $span = $button.find("span");
    const hasText = $span.length > 0;
    const buttonText = hasText ? $span.text() : "";
    const finalLoadingText = loadingText || buttonText;

    $button.prop("disabled", true);

    if (hasText && finalLoadingText) {
        $span.text(finalLoadingText);
        $button.addClass("btn-loading");
    } else {
        $button.append('<span class="submit-spinner"></span>');
        $button.addClass("btn-loading-icon-only");
    }

    return { originalText, originalDisabled };
}

/**
 * Re-enable button and restore original state
 * @param {jQuery} $button - Button element
 * @param {{originalText: string, originalDisabled: boolean}|null} originalState
 */
function enableButton($button, originalState) {
    if (!$button.length || !originalState) return;

    $button.prop("disabled", originalState.originalDisabled);
    $button.html(originalState.originalText);
}

// ============================================================================
// SECTION 2: TOAST NOTIFICATIONS (Notyf)
// ============================================================================

/**
 * Initialize Notyf notification library
 */
const notyf = new Notyf({
    duration: 4000,
    position: { x: "right", y: "top" },
    types: [
        {
            type: "success",
            background: "#f0f9ff",
            color: "#0c4a6e",
            icon: { className: "notyf__icon--success", tagName: "i", text: "✓" },
        },
        {
            type: "error",
            background: "#fef2f2",
            color: "#991b1b",
            icon: { className: "notyf__icon--error", tagName: "i", text: "✕" },
        },
        {
            type: "info",
            background: "#f0f9ff",
            color: "#075985",
            icon: { className: "notyf__icon--info", tagName: "i", text: "ℹ" },
        },
    ],
});

window.showToast = function (message, type = "info", duration = 4000) {
    const toast = { message, duration };

    switch (type) {
        case "success":
            notyf.success(toast);
            break;
        case "error":
            notyf.error(toast);
            break;
        case "info":
        default:
            notyf.info(toast);
            break;
    }
};

window.showMultipleToasts = function (messages, type = "info", duration = 4000) {
    if (!Array.isArray(messages)) messages = [messages];

    messages.forEach((message) => {
        if (message && message.trim()) showToast(message, type, duration);
    });
};

window.showSuccessToast = function (message = "Action completed successfully") {
    showToast(message, "success");
};

window.showErrorToast = function (message = "Something went wrong. Please try again.") {
    showToast(message, "error");
};

window.showInfoToast = function (message = "Information") {
    showToast(message, "info");
};

// ============================================================================
// SECTION 3: SWEETALERT DIALOGS
// ============================================================================

window.SwalConfig = {
    confirm: {
        title: "Are you sure?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: '<i class="fe fe-check me-1"></i> Confirm',
        cancelButtonText: '<i class="fe fe-x me-1"></i> Cancel',
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#6c757d",
        reverseButtons: true,
        buttonsStyling: true,
        customClass: {
            popup: "swal2-modern-popup",
            title: "swal2-modern-title",
            htmlContainer: "swal2-modern-text",
            confirmButton: "swal2-modern-confirm",
            cancelButton: "swal2-modern-cancel",
            icon: "swal2-modern-icon",
        },
        allowOutsideClick: true,
        allowEscapeKey: true,
        focusConfirm: false,
    },
    success: {
        icon: "success",
        confirmButtonText: '<i class="fe fe-check me-1"></i> OK',
        confirmButtonColor: "#3085d6",
        timer: 2000,
        showConfirmButton: false,
        buttonsStyling: true,
        customClass: {
            popup: "swal2-modern-popup",
            title: "swal2-modern-title",
            htmlContainer: "swal2-modern-text",
            confirmButton: "swal2-modern-confirm",
            icon: "swal2-modern-icon",
        },
    },
    error: {
        icon: "error",
        confirmButtonText: '<i class="fe fe-check me-1"></i> OK',
        confirmButtonColor: "#d33",
        buttonsStyling: true,
        customClass: {
            popup: "swal2-modern-popup",
            title: "swal2-modern-title",
            htmlContainer: "swal2-modern-text",
            confirmButton: "swal2-modern-confirm",
            icon: "swal2-modern-icon",
        },
    },
    info: {
        icon: "info",
        confirmButtonText: '<i class="fe fe-check me-1"></i> OK',
        confirmButtonColor: "#3085d6",
        buttonsStyling: true,
        customClass: {
            popup: "swal2-modern-popup",
            title: "swal2-modern-title",
            htmlContainer: "swal2-modern-text",
            confirmButton: "swal2-modern-confirm",
            icon: "swal2-modern-icon",
        },
    },
};

window.showSwal = function (type, options = {}) {
    const baseConfig = window.SwalConfig[type] || {};
    const config = { ...baseConfig, ...options };

    if (options.customClass) {
        config.customClass = { ...baseConfig.customClass, ...options.customClass };
    }

    return Swal.fire(config);
};

// ============================================================================
// SECTION 4: FORM VALIDATION
// ============================================================================

window.validateForm = function (formId) {
    const $form = $("#" + formId);
    if ($form.length === 0) return false;

    const $inputs = $form.find("input[required], select[required], textarea[required]");
    let isValid = true;

    $inputs.each(function () {
        const $input = $(this);
        if (!$input.val() || !$input.val().toString().trim()) {
            $input.addClass("is-invalid");
            isValid = false;
            showToast(`Please fill field ${$input.attr("name") || $input.attr("id")}`, "error");
        } else {
            $input.removeClass("is-invalid");
        }
    });

    return isValid;
};

// ============================================================================
// SECTION 5: UTILITY FUNCTIONS
// ============================================================================

window.copyToClipboard = function (text, message = "Copied to clipboard!") {
    navigator.clipboard
        .writeText(text)
        .then(() => showToast(message, "success", 2000))
        .catch(() => showToast("Failed to copy text", "error"));
};

window.scrollToTop = function () {
    $("html, body").animate({ scrollTop: 0 }, 500);
};

window.initializeFsLightbox = function () {
    if (typeof window.refreshFsLightbox === "function") {
        window.refreshFsLightbox();
    } else if (typeof window.refreshFsLightboxIfAvailable === "function") {
        window.refreshFsLightboxIfAvailable();
    }
};

// ============================================================================
// SECTION 6: DOCUMENT READY - INITIALIZATION
// ============================================================================

$(document).ready(function () {
    $("[data-bs-toggle='tooltip']").tooltip();
    $("[data-bs-toggle='popover']").popover();

    // Confirmation actions
    $(".btn-confirm").on("click", function (e) {
        e.preventDefault();

        const $button = $(this);
        const $form = $button.closest("form");
        const confirmMessage =
            $button.data("confirm-message") || "Are you sure you want to perform this action?";
        const confirmTitle = $button.data("confirm-title") || "Confirm Action";

        Swal.fire({
            title: confirmTitle,
            text: confirmMessage,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: '<i class="fe fe-check me-1"></i> Confirm',
            cancelButtonText: '<i class="fe fe-x me-1"></i> Cancel',
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#6c757d",
            reverseButtons: true,
            buttonsStyling: true,
            customClass: {
                popup: "swal2-modern-popup",
                title: "swal2-modern-title",
                htmlContainer: "swal2-modern-text",
                confirmButton: "swal2-modern-confirm",
                cancelButton: "swal2-modern-cancel",
                icon: "swal2-modern-icon",
            },
            allowOutsideClick: true,
            allowEscapeKey: true,
            focusConfirm: false,
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            disableButtonWithSpinner($button);

            if ($form.length) {
                if ($form[0] && typeof $form[0].submit === "function") {
                    $form[0].submit();
                } else {
                    const tempSubmit = document.createElement("input");
                    tempSubmit.type = "submit";
                    tempSubmit.style.display = "none";
                    $form[0].appendChild(tempSubmit);
                    tempSubmit.click();
                    tempSubmit.remove();
                }
            }
        });
    });

    // Auto-disable submit buttons
    $(document).on("submit", "form", function () {
        const $submitBtn = $(this).find('button[type="submit"], input[type="submit"]');
        if ($submitBtn.hasClass("btn-confirm")) return;

        if ($submitBtn.length && !$submitBtn.prop("disabled")) {
            disableButtonWithSpinner($submitBtn);
        }
    });

    // Custom validation for hidden required file inputs
    $("form").each(function () {
        const $form = $(this);

        const hasHiddenRequiredFiles =
            $form
                .find('input[type="file"][required]')
                .filter(function () {
                    return $(this).css("display") === "none" || !$(this).is(":visible");
                }).length > 0;

        if (!hasHiddenRequiredFiles) {
            return;
        }

        $form.attr("novalidate", "novalidate");

        $form.on("submit", function (e) {
            let isValid = true;
            const $requiredInputs = $form.find("input[required], select[required], textarea[required]");

            $requiredInputs.each(function () {
                const $input = $(this);
                const inputType = $input.attr("type");

                if (inputType === "file") {
                    const hasExistingFile =
                        $input.data("has-existing-file") === true ||
                        $input.data("has-existing-file") === "true";
                    const hasNewFile = $input[0].files && $input[0].files.length > 0;

                    if (!hasExistingFile && !hasNewFile) {
                        isValid = false;
                        const fieldName = $input.attr("name") || "file";
                        const $wrapper = $input.closest(".modern-image-uploader");

                        $input.addClass("is-invalid");
                        $wrapper.find(".invalid-feedback").remove();
                        $wrapper.after(
                            `<div class="invalid-feedback d-block">Please choose ${fieldName}</div>`,
                        );
                        $wrapper.addClass("border border-danger rounded p-2");
                        return false;
                    }

                    $input.removeClass("is-invalid");
                    $input.closest(".modern-image-uploader").removeClass("border border-danger rounded p-2");
                    $input.closest(".modern-image-uploader").next(".invalid-feedback").remove();
                    return;
                }

                if (inputType === "checkbox" || inputType === "radio") {
                    if (!$input.is(":checked")) {
                        isValid = false;
                        $input.addClass("is-invalid");
                        if (!$input.next(".invalid-feedback").length) {
                            const fieldName = $input.attr("name") || $input.attr("id") || "field";
                            $input.after(
                                `<div class="invalid-feedback d-block">Please select ${fieldName}</div>`,
                            );
                        }
                        return false;
                    }

                    $input.removeClass("is-invalid");
                    $input.next(".invalid-feedback").remove();
                    return;
                }

                const value = $input.val();
                if (!value || (typeof value === "string" && !value.trim())) {
                    isValid = false;
                    $input.addClass("is-invalid");
                    if (!$input.next(".invalid-feedback").length) {
                        const fieldName = $input.attr("name") || $input.attr("id") || "field";
                        $input.after(
                            `<div class="invalid-feedback d-block">Please fill ${fieldName}</div>`,
                        );
                    }
                    return false;
                }

                $input.removeClass("is-invalid");
                $input.next(".invalid-feedback").remove();
            });

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });

    $(document).on("focus", ".is-invalid", function () {
        $(this).removeClass("is-invalid");
        $(this).next(".invalid-feedback").remove();
    });

    $(document).on("change", "input[type='file'].is-invalid", function () {
        const $input = $(this);
        const hasNewFile = $input[0].files && $input[0].files.length > 0;

        if (!hasNewFile) {
            return;
        }

        $input.removeClass("is-invalid");
        $input.closest(".modern-image-uploader").removeClass("border border-danger rounded p-2");
        $input.closest(".modern-image-uploader").next(".invalid-feedback").remove();
    });
});

// ============================================================================
// SECTION 7: TINYMCE EDITOR INITIALIZATION
// ============================================================================

document.addEventListener("DOMContentLoaded", function () {
    if (!window.tinymce) return;

    const textareas = document.querySelectorAll('textarea[data-editor="tinymce"]');

    textareas.forEach(function (textarea) {
        const maxHeight = textarea.dataset.tinymceMaxHeight || 420;

        tinymce.init({
            target: textarea,
            license_key: "gpl",
            height: maxHeight,
            max_height: parseInt(maxHeight, 10),
            menubar: false,
            branding: false,
            directionality: "ltr",
            plugins: "lists link image table code",
            toolbar: [
                "undo redo | blocks | bold italic underline | alignleft aligncenter alignright |",
                "bullist numlist | link image table | code",
            ].join(" "),
            toolbar_sticky: true,
            toolbar_sticky_offset: 0,
            resize: false,
            convert_urls: true,
            relative_urls: false,
            remove_script_host: false,
            content_style: `body { overflow-y: auto; max-height: ${parseInt(maxHeight, 10) - 80}px; }`,
            images_upload_url: "/dashboard/v2/tinymce/upload",
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    const formData = new FormData();
                    formData.append("file", blobInfo.blob(), blobInfo.filename());
                    const uploadFolder = textarea.dataset.tinymceUploadFolder || "others";
                    formData.append("folder", uploadFolder);

                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "/dashboard/v2/tinymce/upload");

                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (csrfToken) {
                        xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken.getAttribute("content"));
                    }

                    xhr.upload.onprogress = function (e) {
                        progress((e.loaded / e.total) * 100);
                    };

                    xhr.onload = function () {
                        let json;
                        try {
                            json = JSON.parse(xhr.responseText);
                        } catch (e) {
                            reject("Invalid JSON response from server.");
                            return;
                        }

                        if (xhr.status === 403) {
                            reject({ message: `HTTP Error: ${xhr.status} - Access forbidden`, remove: true });
                            return;
                        }

                        if (xhr.status < 200 || xhr.status >= 300) {
                            const errorMsg = json.error || json.message || "Upload failed";
                            reject(`HTTP Error: ${xhr.status} - ${errorMsg}`);
                            return;
                        }

                        if (!json || typeof json.location !== "string") {
                            reject(json.error || "Invalid response from server");
                            return;
                        }

                        resolve(json.location);
                    };

                    xhr.onerror = function () {
                        reject(`Image upload failed due to a XHR transport error. Code: ${xhr.status}`);
                    };

                    xhr.send(formData);
                });
            },
        });
    });
});

// ============================================================================
// SECTION 8: SELECT2 INITIALIZATION
// ============================================================================

document.addEventListener("DOMContentLoaded", function () {
    const select2Elements = document.querySelectorAll('select[data-select2-init="true"]');

    if (window.jQuery && typeof window.jQuery.fn.select2 === "function") {
        select2Elements.forEach(function (element) {
            if (window.jQuery(element).hasClass("select2-hidden-accessible")) {
                return;
            }

            const configuredMinimumResults = Number.parseInt(
                element.dataset.minimumResultsForSearch,
                10
            );
            const config = {
                theme: element.dataset.theme || "bootstrap4",
                width: element.dataset.width || "100%",
                placeholder:
                    element.dataset.placeholder || (element.multiple ? "Select options" : "Select an option"),
                allowClear: true,
                minimumResultsForSearch: Number.isNaN(configuredMinimumResults)
                    ? 1
                    : configuredMinimumResults,
                closeOnSelect: element.dataset.closeOnSelect === "true",
                dir: document.documentElement.dir === "rtl" ? "rtl" : "ltr",
                language: {
                    noResults: function () {
                        return "No results found";
                    },
                    searching: function () {
                        return "Searching...";
                    },
                    removeAllItems: function () {
                        return "Remove all items";
                    },
                    inputTooShort: function () {
                        return "Please enter more characters";
                    },
                    inputTooLong: function () {
                        return "Input is too long";
                    },
                    errorLoading: function () {
                        return "Error loading data";
                    },
                    loadingMore: function () {
                        return "Loading more...";
                    },
                },
            };

            if (element.multiple) {
                config.placeholder = element.dataset.placeholder || "Select options";
            }

            $(element).select2(config);
        });
    }

    initModernImageUploaders();
});

function initModernImageUploaders() {
    $(".modern-file-input").each(function () {
        const $input = $(this);
        const inputId = $input.attr("id");
        const previewBaseId = inputId.replace("img_uploader_", "preview_");
        const $nameDisplay = $("#" + inputId + "_name");

        if ($input.data("initialized")) return;
        $input.data("initialized", true);

        $input.on("change", function () {
            const $preview = $("#" + previewBaseId);
            const $previewLink = $("#" + previewBaseId + "_link");
            const $wrapper = $("#" + previewBaseId + "_wrapper");
            const file = this.files[0];

            if (file) {
                const fileName = file.name;
                const maxLength = 30;
                let displayName = fileName;

                if (fileName.length > maxLength) {
                    const extension = fileName.substring(fileName.lastIndexOf("."));
                    const nameWithoutExt = fileName.substring(0, fileName.lastIndexOf("."));
                    const truncatedName = nameWithoutExt.substring(0, maxLength - extension.length - 3);
                    displayName = truncatedName + "..." + extension;
                }

                $nameDisplay.val(displayName).removeClass("text-muted").addClass("text-dark");
                if ($wrapper.length) {
                    $wrapper.removeClass("d-none");
                }

                if (file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imageUrl = e.target.result;

                        if ($preview.is("img")) {
                            $preview.attr("src", imageUrl);
                            $previewLink
                                .attr("href", imageUrl)
                                .attr("data-fslightbox", "gallery_" + $input.attr("id"));
                        } else {
                            $preview.replaceWith(
                                '<img src="' +
                                    imageUrl +
                                    '" id="' +
                                    previewBaseId +
                                    '" class="rounded" style="height: 26px; width: 26px; object-fit: cover; cursor: pointer;" alt="Preview">',
                            );
                            $previewLink
                                .attr("href", imageUrl)
                                .attr("data-fslightbox", "gallery_" + $input.attr("id"))
                                .removeAttr("target");
                        }

                        setTimeout(function () {
                            refreshFsLightbox();
                        }, 200);
                    };

                    reader.readAsDataURL(file);
                } else {
                    const fileUrl = URL.createObjectURL(file);
                    const isVideoFile = file.type.startsWith("video/");
                    const previewIcon = isVideoFile ? "fe-video" : "fe-file";
                    const previewTitle = isVideoFile ? "Open video" : "Open file";

                    if ($preview.is("img")) {
                        $preview.replaceWith(
                            '<i class="fe ' +
                                previewIcon +
                                '" id="' +
                                previewBaseId +
                                '" style="font-size: 26px; cursor: pointer; color: #6c757d;" title="' +
                                previewTitle +
                                '"></i>',
                        );
                    } else {
                        $preview
                            .removeClass("fe-file fe-video")
                            .addClass(previewIcon)
                            .attr("title", previewTitle);
                    }

                    $previewLink.attr("href", fileUrl).attr("target", "_blank").removeAttr("data-fslightbox");
                }
            } else {
                const hasPreview = $wrapper.length && !$wrapper.hasClass("d-none");
                $nameDisplay
                    .val(hasPreview ? "Choose another file" : "No file selected")
                    .removeClass("text-dark")
                    .addClass("text-muted");
            }
        });
    });
}

// ============================================================================
// END OF GLOBAL.JS
// ============================================================================
