<!-- jQuery أولاً -->
<script src="{{ asset('/assets/js/jquery.min.js') }}"></script>

<!-- Axios + CSRF -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] =
        document.querySelector('meta[name="csrf-token"]').content;
</script>

<!-- Bootstrap 5 Bundle (مرة واحدة فقط) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- بقية الـ Vendors بترتيب سليم -->
<script src="{{ asset('/assets/js/moment.js') }}"></script>
<script src="{{ asset('/assets/vendor/slimscroll/slimscroll.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/slimscroll/custom-scrollbar.js') }}"></script>

<script src="{{ asset('/assets/vendor/daterange/daterange.js') }}"></script>
<script src="{{ asset('/assets/vendor/daterange/custom-daterange.js') }}"></script>

<script src="{{ asset('/assets/vendor/polyfill/polyfill.min.js') }}"></script>

<script src="{{ asset('/assets/vendor/apex/apexcharts.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/visitors.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/deals.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/income.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/customers.js') }}"></script>

<script src="{{ asset('/assets/js/main.js') }}"></script>

<script src="{{ asset('/assets/vendor/particles/particles.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/particles/particles-custom-error.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"
    integrity="sha512-Ixzuzfxv1EqafeQlTCufWfaC6ful6WFqIz4G+dWvK0beHw0NVJwvCKSgafpy5gwNqKmgUfIBraVwkKI+Cz0SEQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6/dist/js/tempus-dominus.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const jsTranslations = {
        formNotFound: @json(__('js.form_not_found')),
        enterTextHere: @json(__('js.enter_text_here')),
        maxCharacters: @json(__('js.max_characters')),
        bootstrapMultiselectNotLoaded: @json(__('js.bootstrap_multiselect_not_loaded')),
        selectAll: @json(__('js.select_all')),
        allSelected: @json(__('js.all_selected')),
        chooseRoles: @json(__('js.choose_roles')),
        choosePermissions: @json(__('js.choose_permissions')),
    };

    function checker(ev, item) {
        ev.preventDefault();
        Swal.fire({
            title: '{{ __('admin.sweet_alert.delete_title') }}',
            text: '{{ __('admin.sweet_alert.delete_text') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('admin.sweet_alert.confirm_button') }}',
            confirmButtonColor: '#d9534f',
            cancelButtonText: '{{ __('admin.sweet_alert.cancel_button') }}',
            cancelButtonColor: '#028a0f',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const formId = 'delete_form_' + item;
                var form = document.getElementById(formId);
                if (form) form.submit();
                else console.error(jsTranslations.formNotFound.replace(':id', formId));
            } else {
                Swal.fire(
                    '{{ __('admin.sweet_alert.cancelled') }}',
                    '{{ __('admin.sweet_alert.data_safe') }}',
                    'error'
                );
            }
        });
    }
</script>

<!-- Bootstrap Multiselect (نسخة واحدة فقط) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.min.js">
</script>

<!-- Summernote -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<!-- تهيئة Summernote + Multiselect (متوافقة مع BS5) -->
<script>
    $(function() {
        /* Summernote */
        $('.summernote').summernote({
            placeholder: jsTranslations.enterTextHere,
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['bold', 'underline', 'clear']],
                ['para', ['ul', 'ol']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']]
            ],
            fontNames: ['Arial', 'Courier New', 'Georgia', 'Segoe UI', 'Tahoma', 'Times New Roman',
                'Verdana'
            ],
            fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
            callbacks: {
                onPaste: function(e) {
                    var bufferText = ((e.originalEvent || e).clipboardData).getData('Text');
                    e.preventDefault();
                    document.execCommand('insertText', false, bufferText);
                }
            }
        });

        // عداد أحرف بسيط
        $('.summernote').each(function() {
            var $textarea = $(this);
            var max = $textarea.data('maxlength');
            var $editor = $textarea.next('.note-editor');
            var $countEl = $(
                '<span class="char-counter">' + jsTranslations.maxCharacters +
                ' (<span class="char-count text-muted">0</span>/' + (max || '∞') + ')</span>');
            $editor.after($countEl);

            function updateCount() {
                var plain = $('<div>').html($textarea.summernote('code')).text();
                $countEl.find('.char-count').text(plain.length);
                if (max && plain.length > max) {
                    $countEl.find('.char-count').addClass('text-danger');
                } else {
                    $countEl.find('.char-count').removeClass('text-danger');
                }
            }
            $textarea.on('summernote.change keyup', updateCount);
            updateCount();
        });

        /* Multiselect */
        if (typeof $.fn.multiselect !== 'function') {
            console.error(jsTranslations.bootstrapMultiselectNotLoaded);
            return;
        }

        function bs5ButtonTemplate() {
            return '<button type="button" class="multiselect dropdown-toggle w-100 text-start" data-bs-toggle="dropdown">' +
                '<span class="multiselect-selected-text"></span> <b class="caret"></b>' +
                '</button>';
        }

        function initMulti($el, nonText) {
            if (!$el.length) return;
            if ($el.data('multiselect')) {
                try {
                    $el.multiselect('destroy');
                } catch (e) {}
            }
            $el.multiselect({
                includeSelectAllOption: true,
                selectAllText: jsTranslations.selectAll,
                allSelectedText: jsTranslations.allSelected,
                nonSelectedText: nonText,
                buttonWidth: '100%',
                buttonClass: 'btn btn-light w-100 text-start',
                maxHeight: 220,
                numberDisplayed: 3,
                buttonContainer: '<div class="btn-group w-100" />',
                container: 'body',
                templates: {
                    button: bs5ButtonTemplate()
                },
                onInitialized: function($select, container) {
                    if (document.documentElement.getAttribute('dir') === 'rtl') {
                        container.find('.multiselect-container.dropdown-menu').addClass(
                            'dropdown-menu-end text-end');
                    }
                }
            });
        }
        initMulti($('#roles'), jsTranslations.chooseRoles);
        initMulti($('#permissions'), jsTranslations.choosePermissions);
    });
</script>
