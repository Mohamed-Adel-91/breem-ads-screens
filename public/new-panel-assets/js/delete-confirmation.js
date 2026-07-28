/**
 * Centralized SweetAlert confirmation for CMS destructive actions.
 */
document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!form.classList.contains('js-delete-form') || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    if (typeof Swal === 'undefined') {
        return;
    }

    const lang = document.documentElement.lang === 'ar' ? 'ar' : 'en';
    const defaults = window.cmsDeleteDefaults || {};

    Swal.fire({
        title: form.dataset.deleteTitle || defaults.title,
        text: form.dataset.deleteMessage || defaults.message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: form.dataset.deleteConfirmText || defaults.confirm,
        cancelButtonText: form.dataset.deleteCancelText || defaults.cancel,
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-modern-popup',
            title: 'swal2-modern-title',
            htmlContainer: 'swal2-modern-text',
            confirmButton: 'swal2-modern-confirm ahcl-swal-confirm',
            cancelButton: 'swal2-modern-cancel ahcl-swal-cancel',
            icon: 'swal2-modern-icon'
        },
        iconColor: '#d1a52a',
        reverseButtons: lang === 'ar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
});
