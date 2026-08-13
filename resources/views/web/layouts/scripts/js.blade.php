<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.min.js">
</script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="{{ asset('frontend/js/main.js') }}"></script>
@stack('scripts-js')
<script>
    var swiper = new Swiper(".slider .mySwiper", {
        slidesPerView: 5,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 2000,
            disableOnInteraction: false,
        },
        breakpoints: {
            0: {
                slidesPerView: 2,
            },
            768: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 4,
            },
            1200: {
                slidesPerView: 5,
            },
        },
    });

    /*
     * Locations carousel.
     *
     * The carousel is CONTAINED, not edge to edge: its wrapper is `.site-container`, so
     * Swiper measures the site's measure rather than the viewport. Before, `.container`
     * ran away above 1600px, which is what made the end cards look cut off by the window
     * — the clipping was accidental, not a design hint.
     *
     * Desktop shows 3 WHOLE cards. The previous 3.5 put a half card on one side only,
     * which reads as a rendering fault rather than an affordance; the arrows and bullets
     * below already say the row scrolls. The fractional peek is kept where it is a real
     * convention and symmetry is not expected — a phone, and the tablet step between.
     */
    var whereSwiper = new Swiper(".whereSwiper", {
        spaceBetween: 35,
        loop: true,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        breakpoints: {
            0: {
                slidesPerView: 1.15,
                spaceBetween: 16,
            },
            576: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2.5,
                spaceBetween: 24,
            },
            992: {
                slidesPerView: 3,
                spaceBetween: 35,
            }
        }
    });
</script>
<!-- Scripts Stack JS -->
<script>
    document.querySelectorAll('a[href="#"]').forEach(el => {
        el.addEventListener('click', e => e.preventDefault());
    });
</script>
<!-- SweetAlert for flash messages -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    (function() {
        const swal = @json(session('swal'));
        if (swal && swal.type && swal.text) {
            Swal.fire({
                icon: swal.type,
                title: swal.type === 'success' ?
                    '{{ app()->getLocale() === 'ar' ? 'تم بنجاح' : 'Success' }}' :
                    '{{ app()->getLocale() === 'ar' ? 'خطأ' : 'Error' }}',
                text: swal.text,
            });
        }
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: '{{ app()->getLocale() === 'ar' ? 'خطأ في التحقق' : 'Validation Error' }}',
                html: `{!! implode('<br>', array_map('e', $errors->all())) !!}`,
            });
        @endif
    })();
</script>
