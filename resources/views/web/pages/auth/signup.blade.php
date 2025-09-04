@extends('web.layouts.master')

@push('meta')
    <!-- SEO Meta -->
<meta name="author" content="Icon Creations" />
<link rel="shortcut icon" type="image/x-icon" href="img/homepage/logo.ico" />
    <title>التوكيل | الموقع الرسمي لبيع وشراء السيارات في مصر</title>
    <meta name="description"
        content="التوكيل هو موقعك الأول لشراء وبيع السيارات الجديدة والمستعملة، مع آخر الأخبار والعروض، ودليلك الكامل لعالم السيارات." />
    <meta name="keywords"
        content="التوكيل, سيارات جديدة, سيارات مستعملة, مقارنة سيارات, أسعار السيارات, شراء سيارة, بيع سيارة" />
    <!-- Open Graph for Social Sharing -->
    <meta property="og:title" content="التوكيل | موقع السيارات الأول في مصر" />
    <meta property="og:description" content="اكتشف أحدث السيارات، قارن بين الموديلات، تابع آخر العروض والأخبار." />
    <meta property="og:image" content="https://dev-iconcreations.com/el-tawkeel/public/frontend/img/homepage/og-image.png" />
    <meta property="og:url" content="https://eltawkeel.com/" />
    <meta property="og:type" content="website" />
@endpush

@section('content')
    <!-- Main Content -->
    <main>
        <section class="login_section">
            <div class="flex-div lg:mx-0 lg:-mr-2">
                <div class="w-full lg:w-1/3 px-2 py-4">
                    <h2 class="custom_title">
                        <span>إنشاء حساب جديد</span>
                    </h2>
                    <div class="lg:px-7 xl:px-16">
                        <form id="signupForm" action="{{ route('web.register') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form_group">
                                <input name="full_name" class="form_group_control " type="text" placeholder="الأسم كامل "
                                    value="{{ old('full_name') }}">
                                <!-- <label class="form_group_label">الأسم كامل</label> -->
                                <span class="error text-red-600" data-error-for="full_name"></span>
                            </div>
                            <div class="form_group">
                                <input name="nickname" class="form_group_control " type="text" placeholder="الأسم المستعار "
                                    value="{{ old('nickname') }}">
                                <!-- <label class="form_group_label">الأسم المستعار</label> -->
                                <span class="error text-red-600" data-error-for="nickname"></span>
                            </div>
                            <div class="form_group">
                                <input name="email" class="form_group_control " type="email" placeholder=" الإيميل"
                                    value="{{ old('email') }}">
                                <!-- <label class="form_group_label">الإيميل</label> -->
                                <span class="error text-red-600" data-error-for="email"></span>
                            </div>
                            <div class="form_group">
                                <input name="mobile" class="form_group_control " type="tel" placeholder="رقم الموباي "
                                    value="{{ old('mobile') }}">
                                <!-- <label class="form_group_label">رقم الموبايل</label> -->
                                <span class="error text-red-600" data-error-for="mobile"></span>
                            </div>
                            <div class="form_group">
                                <input name="password" id="passwordInput" class="form_group_control" type="password"
                                    placeholder=" الرقم السري">
                                <!-- <label class="form_group_label">الرقم السري</label> -->
                                <span class="form_group_passwordEye" id="togglePassword" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </span>
                                <span class="error text-red-600" data-error-for="password"></span>
                            </div>
                            <div class="form_group">
                                <input name="password_confirmation" id="passwordConfirmInput" class="form_group_control"
                                    type="password" placeholder="تأكيد الرقم السري ">
                                <!-- <label class="form_group_label">تأكيد الرقم السري</label> -->
                                <span class="form_group_passwordEye" id="togglePasswordConfirm" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </span>
                                <span class="error text-red-600" data-error-for="password_confirmation"></span>
                            </div>
                            <div class="button flex items-center justify-center">
                                <button class="form-button">تسجيل</button>
                            </div>
                        </form>
                        <div class="py-4">
                            <h5 class="another_title">أو عن طريق</h5>
                            @include('web.pages.auth.partials.social-signup')
                        </div>
                    </div>
                </div>
                <div class="w-full lg:w-2/3 lg:pr-2 hidden lg:block">
                    <div class="img_block h-full">
                        <img src="img/homepage/login.png" class="w-full" alt="">
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts-js')
    <script>
        (function() {
            const form = document.getElementById('signupForm');
            if (!form) return;

            function clearErrors() {
                document.querySelectorAll('[data-error-for]').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
            }

            function showFieldError(name, message) {
                const el = document.querySelector(`[data-error-for="${name}"]`);
                if (el) {
                    el.textContent = message;
                    el.style.display = '';
                }
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearErrors();
                const url = form.getAttribute('action');
                const fd = new FormData(form);
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: fd,
                        credentials: 'same-origin'
                    });
                    const json = await res.json();
                    if (res.ok) {
                        Swal.fire({
                            icon: 'success',
                            text: json.message,
                            confirmButtonColor: '#d03b37',
                        }).then(() => {
                            window.location.href = @json(route('web.home'));
                        });
                    } else {
                        if (json?.errors) {
                            Object.entries(json.errors).forEach(([field, messages]) => {
                                showFieldError(field, Array.isArray(messages) ? messages[0] :
                                    messages);
                            });
                        }
                        Swal.fire({
                            icon: 'error',
                            text: json.message,
                            confirmButtonColor: '#d03b37',
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        text: 'تعذر الاتصال بالخادم',
                        confirmButtonColor: '#d03b37',
                    });
                }
            });
        })();
    </script>
@endpush
