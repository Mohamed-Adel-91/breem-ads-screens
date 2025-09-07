<!-- Required jQuery first, then Bootstrap Bundle JS -->
<script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] =
        document.querySelector('meta[name="csrf-token"]').content;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('/assets/js/moment.js') }}"></script>
<!-- Slimscroll JS -->
<script src="{{ asset('/assets/vendor/slimscroll/slimscroll.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/slimscroll/custom-scrollbar.js') }}"></script>
<!-- Daterange -->
<script src="{{ asset('/assets/vendor/daterange/daterange.js') }}"></script>
<script src="{{ asset('/assets/vendor/daterange/custom-daterange.js') }}"></script>
<!-- Polyfill JS -->
<script src="{{ asset('/assets/vendor/polyfill/polyfill.min.js') }}"></script>
<!-- Apex Charts -->
<script src="{{ asset('/assets/vendor/apex/apexcharts.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/visitors.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/deals.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/income.js') }}"></script>
<script src="{{ asset('/assets/vendor/apex/admin/customers.js') }}"></script>
<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('/assets/vendor/particles/particles.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/particles/particles-custom-error.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"
    integrity="sha512-Ixzuzfxv1EqafeQlTCufWfaC6ful6WFqIz4G+dWvK0beHw0NVJwvCKSgafpy5gwNqKmgUfIBraVwkKI+Cz0SEQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6/dist/js/tempus-dominus.min.js"></script>

{{-- cdn for sweetalert --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function checker(ev, item) {
        // Prevent the default form submission
        ev.preventDefault();
        // Use SweetAlert2 to display a confirmation modal
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
                // If user confirms, submit the form with the given ID
                var form = document.getElementById('delete_form_' + item);
                if (form) {
                    form.submit();
                } else {
                    console.error('Form not found: delete_form_' + item);
                }
            } else {
                // Optional: handle the case when the user cancels the deletion
                Swal.fire(
                    '{{ __('admin.sweet_alert.cancelled') }}',
                    '{{ __('admin.sweet_alert.data_safe') }}',
                    'error'
                );
            }
        });
    }
</script>
{{-- cdn for Multiselect --}}
<script type="text/javascript" src="{{ asset('assets/js/popper.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap-multiselect.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/js/all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.min.js">
</script>
{{-- cdn for summernote --}}
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            placeholder: 'Enter text here...',
            tabsize: 2,
            height: 200,
            // 1) ONLY these toolbar buttons:
            toolbar: [
                // [groupName, [buttonList]]
                ['style', ['bold', 'underline', 'clear']],
                // ['font', [ 'fontNames', 'color' , 'fontSizes']],
                ['para', ['ul', 'ol']],
                ['insert', [
                    'link',
                    'picture'
                ]],
                ['view', ['codeview']]
            ],
            // 2) YOUR approved fonts & sizes
            fontNames: [
                'Arial',
                'Courier New',
                'Georgia',
                'Segoe UI',
                'Tahoma',
                'Times New Roman',
                'Verdana'
            ],
            fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
            // 3) Strip all styling on paste, only plain text
            callbacks: {
                onPaste: function(e) {
                    var bufferText = ((e.originalEvent || e).clipboardData).getData('Text');
                    e.preventDefault();
                    // insert as plain text
                    document.execCommand('insertText', false, bufferText);
                }
            }
        });
        // Add character counter below each Summernote editor
        $('.summernote').each(function() {
            var $textarea = $(this);
            var max = $textarea.data('maxlength');
            var $editor = $textarea.next('.note-editor');
            var $countEl = $(
                '<span class="char-counter">Max Characters (<span class="char-count text-muted">0</span>/' +
                max + ')</span>');
            $editor.after($countEl);
            var $submit = $textarea.closest('form').find('button[type="submit"]');

            function updateCount() {
                var plain = $('<div>').html($textarea.summernote('code')).text();
                $countEl.find('.char-count').text(plain.length);
                if (max && plain.length > max) {
                    $countEl.find('.char-count').addClass('text-danger');
                    // $submit.prop('disabled', true);
                } else {
                    $countEl.find('.char-count').removeClass('text-danger');
                    $submit.prop('disabled', false);
                }
            }
            $textarea.on('summernote.change keyup', updateCount);
            updateCount();

            // Trim spaces and remove empty elements before submitting the form
            $textarea.closest('form').on('submit', function() {
                var html = $textarea.summernote('code');
                var $wrapper = $('<div>').html(html);
                $wrapper.find('*').each(function() {
                    var $el = $(this);
                    if ($el.children().length === 0 && $el.text().trim() === '') {
                        $el.remove();
                    }
                });
                html = $wrapper.html().replace(/&nbsp;/g, ' ').trim();
                $textarea.summernote('code', html);
            });
        });
    });
</script>
