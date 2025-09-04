<footer>
    <div class="footerground py-5">
        <div class="container">
            <div class="footerground_flex flex items-center flex-wrap w-full -mx-4">
                <div class="px-4 relative ">
                    <div class="mb-4">
                        <span>
                            <img src="img/homepage/gray_logo.png" alt="Logo">
                        </span>
                    </div>
                    <h5 class="text-white text-lg mb-3">دخل بريدك الإلكتروني لإستقبال نشرتنا الإخبارية</h5>
                    <form id="newsletter-form" action="{{ route('web.newsletter.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="newsletter_form_group">
                            <input type="email" id="newsEmail" name="email" placeholder="البريد الالكتروني"
                                class="form-input block w-full rounded-md bg-transparent border text-white placeholder:text-white focus:main-border">
                            <button type="submit" class="newsletter_form_group_button rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-7">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            </button>
                        </div>

                    </form>
                </div>
                <div class="px-4 relative ">
                    <ul class="list-none text-white">
                        <li class="mb-4">
                            <a href="{{ $setting->location }}" class="inline-flex items-center gap-3 hover-link">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    aria-hidden="true" class="w-5">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $setting->address }}</span>
                            </a>
                        </li>
                        <li class="mb-4">
                            <a href="mailto:{{ $setting->email }}" class="inline-flex items-center gap-3 hover-link">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    aria-hidden="true" class="w-5">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z">
                                    </path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                                <span>{{ $setting->email }}</span>
                            </a></li>
                    </ul>
                </div>
                <div class="px-4 relative">
                    <ul class="list-none text-white">
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.new-cars') }}">
                                <span>السيارات الجديدة</span>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.comparison') }}">
                                <span>مقارنات السيارات</span>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.news') }}">
                                <span>أخبار السيارات</span>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.videos') }}">
                                <span>فيديوهات</span>
                            </a>
                        </li>

                    </ul>
                </div>
                <div class="px-4 relative">
                    <ul class="list-none text-white">
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.installment') }}">
                                <span>التقسيط</span>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.insurance') }}">
                                <span>التأمين</span>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a class="text-base hover-link" href="{{ route('web.service-centers') }}">
                                <span>مراكز الخدمه</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="w-1/6 px-4">
                    <h5 class="text-lg text-white mb-5">تابعنا على</h5>
                    <ul class="list-none flex items-center justify-center gap-4 social_icons">
                        <li class="">
                            <a href="{{ $setting->facebook }}" target="_blank" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11.449" height="21.377"
                                    viewBox="0 0 11.449 21.377">
                                    <path id="Icon_awesome-facebook-f" data-name="Icon awesome-facebook-f"
                                        d="M12.308,12.025,12.9,8.156H9.19V5.645a1.934,1.934,0,0,1,2.181-2.09h1.688V.261a20.58,20.58,0,0,0-3-.261C7.006,0,5.008,1.853,5.008,5.207V8.156h-3.4v3.869h3.4v9.352H9.19V12.025Z"
                                        transform="translate(-1.609)" fill="#fff"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="">
                            <a href="{{ $setting->instagram }}" target="_blank" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17.869" height="17.869"
                                    viewBox="0 0 17.869 17.869">
                                    <g id="instagram_4_" data-name="instagram (4)" transform="translate(-0.328 0)">
                                        <path id="Path_5374" data-name="Path 5374"
                                            d="M13.031,0h-8.2A4.843,4.843,0,0,0,0,4.837v8.2a4.842,4.842,0,0,0,4.837,4.837h8.2a4.842,4.842,0,0,0,4.837-4.837v-8.2A4.843,4.843,0,0,0,13.031,0Zm-4.1,13.821A4.886,4.886,0,1,1,13.82,8.935,4.891,4.891,0,0,1,8.934,13.821Zm5-8.62A1.444,1.444,0,1,1,15.38,3.757,1.445,1.445,0,0,1,13.937,5.2Zm0,0"
                                            transform="translate(0.329 0)" fill="#fff"></path>
                                        <path id="Path_5375" data-name="Path 5375"
                                            d="M149.51,146.02a3.49,3.49,0,1,0,3.49,3.49A3.494,3.494,0,0,0,149.51,146.02Zm0,0"
                                            transform="translate(-140.247 -140.575)" fill="#fff"></path>
                                        <path id="Path_5376" data-name="Path 5376"
                                            d="M388.389,96.3a.4.4,0,1,0,.4.4A.4.4,0,0,0,388.389,96.3Zm0,0"
                                            transform="translate(-374.386 -92.94)" fill="#fff"></path>
                                    </g>
                                </svg>
                            </a>
                        </li>
                        <li class="">
                            <a href="{{ $setting->youtube }}" target="_blank" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="19.607" height="13.728"
                                    viewBox="0 0 19.607 13.728">
                                    <g id="youtube" transform="translate(0 0)">
                                        <path id="Subtraction_2" data-name="Subtraction 2"
                                            d="M9.8,13.728c-.061,0-6.139,0-7.672-.42A2.457,2.457,0,0,1,.4,11.58,25.918,25.918,0,0,1,0,6.864,26.012,26.012,0,0,1,.4,2.149,2.514,2.514,0,0,1,2.132.4,59.846,59.846,0,0,1,9.8,0c.061,0,6.155,0,7.672.42A2.455,2.455,0,0,1,19.2,2.149a24.684,24.684,0,0,1,.4,4.732,26.058,26.058,0,0,1-.4,4.716,2.457,2.457,0,0,1-1.729,1.728A59.836,59.836,0,0,1,9.8,13.728ZM7.85,3.925V9.8l5.1-2.939-5.1-2.94Z"
                                            transform="translate(0 0)" fill="#fff"></path>
                                    </g>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</footer>

@push('scripts-js')
    <script>
        $('#newsletter-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الاشتراك بنجاح',
                        confirmButtonColor: '#d03b37'
                    });
                    form[0].reset();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'حدث خطأ ما';
                    Swal.fire({
                        icon: 'error',
                        title: message,
                        confirmButtonColor: '#d03b37'
                    });
                }
            });
        });
    </script>
@endpush
