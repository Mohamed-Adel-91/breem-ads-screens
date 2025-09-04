<!-- Scripts - Fixed Order -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<!-- Swiper JS - Load before custom scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/9.4.1/swiper-bundle.min.js"></script>
<!-- WOW.js for animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Main Script -->
<script src="js/script.js"></script>
<!-- Scripts Stack JS -->
<script>
    document.querySelectorAll('a[href="#"]').forEach(el => {
        el.addEventListener('click', e => e.preventDefault());
    });
</script>
@stack('scripts-js')
