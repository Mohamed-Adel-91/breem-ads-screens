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
    <section class="profile ">
        <div class="container">
            <div class="md:flex gap-16">
                <div class="w-full md:mb-0 mb-8 md:w-72 p-8 pl-0 pb-0 pr-0 text-center profile_user">
                    <div class="flex justify-center mb-4 relative">
                        <img src="{{ $user?->image_path ?? 'img/profile/user.png' }}" alt="{{ $user?->full_name }}"
                            class="profile_user_img" id="profile_image_preview" />
                        <button type="button" class="absolute bottom-0 left-15 bg-red-500 text-white rounded-full p-2"
                            onclick="document.getElementById('profile_image').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h4l2-2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <h3 class="mb-6 profile_user_title en">{{ $user?->full_name }}<br />{{ $user?->nickname }}</h3>
                    <div class="text-right space-y-4">
                        <div class="field">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center justify-between">
                                    <span id="phone" class="edit text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            width="9" height="14" viewBox="0 0 9 14" fill="none">
                                            <path
                                                d="M0.440958 11.8571L1.33378 9.94482C1.46757 9.65845 1.7581 9.47351 2.07401 9.47351C2.15214 9.47351 2.23026 9.48499 2.3057 9.5072L3.36698 9.78662C3.51982 9.65503 4.00858 9.12915 4.87968 7.26355C5.7559 5.38696 5.84232 4.68213 5.84428 4.48486L4.94731 3.84985C4.61308 3.62793 4.4893 3.18762 4.65898 2.82385L5.5518 0.911499C5.69096 0.614136 5.99296 0.421875 6.32182 0.421875C6.48466 0.421875 6.64384 0.468994 6.78153 0.558105L7.31571 0.903442C7.78959 1.19604 8.12382 1.64111 8.30863 2.22559C8.47148 2.73962 8.51884 3.36487 8.44999 4.08411C8.33378 5.29382 7.87724 6.80164 7.16361 8.32983C6.54594 9.65222 5.7974 10.8501 5.05522 11.7026C4.10917 12.7899 3.17753 13.3413 2.2869 13.3413C2.04154 13.3413 1.79838 13.2983 1.56449 13.2135L0.959512 13.0267C0.725137 12.9543 0.533487 12.7842 0.434122 12.5599C0.334757 12.3356 0.337198 12.0795 0.440958 11.8571Z"
                                                fill="#656565" />
                                        </svg>
                                        رقم الهاتف

                                    </span>
                                </div>
                                <span id="phone" class="edit">
                                    {{ $user?->mobile }}
                                </span>
                            </div>
                        </div>
                        <div class="field">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center justify-between">
                                    <span id="phone" class="edit text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="12"
                                            viewBox="0 0 15 12" fill="none">
                                            <path
                                                d="M8.32057 7.98004C8.04161 8.21649 7.6996 8.33472 7.35768 8.33472C7.01576 8.33472 6.67377 8.21649 6.39479 7.98004L5.18327 6.95312L1.06836 11.0681H13.647L9.53215 6.95312L8.32057 7.98004Z"
                                                fill="#656565" />
                                            <path d="M14.3535 10.3623V2.86719L10.2969 6.30566L14.3535 10.3623Z"
                                                fill="#656565" />
                                            <path d="M0.361328 2.86719V10.3623L4.41797 6.30566L0.361328 2.86719Z"
                                                fill="#656565" />
                                            <path
                                                d="M13.8356 0.929688H0.87915C0.593628 0.929688 0.361328 1.16199 0.361328 1.44751V1.55484L0.634491 1.78632L7.04073 7.21645C7.22412 7.37199 7.49048 7.37192 7.67407 7.21645L14.0803 1.78632L14.3534 1.55484V1.44751C14.3534 1.16199 14.1211 0.929688 13.8356 0.929688Z"
                                                fill="#656565" />
                                        </svg> اللإيميل
                                    </span>
                                </div>
                                <span>{{ $user?->email }}</span>

                            </div>
                        </div>
                        <div class="field">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center justify-between">
                                    <span id="phone" class="edit text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="21"
                                            viewBox="0 0 15 21" fill="none">
                                            <path
                                                d="M7.13638 0.078125C3.41043 0.078125 0.390015 3.11766 0.390015 6.86705C0.390015 10.6165 7.13638 20.6713 7.13638 20.6713C7.13638 20.6713 13.8825 10.6165 13.8825 6.86705C13.8825 3.11766 10.8622 0.078125 7.13638 0.078125ZM7.13638 10.8434C4.84758 10.8434 2.99218 8.9763 2.99218 6.67308C2.99218 4.36986 4.84758 2.50279 7.13638 2.50279C9.42504 2.50279 11.2806 4.36986 11.2806 6.67308C11.2806 8.9763 9.42504 10.8434 7.13638 10.8434Z"
                                                fill="#656565" />
                                            <path
                                                d="M7.13513 20.8125L6.8127 20.3515C6.79583 20.3272 5.09847 17.894 3.42406 15.0519C1.15198 11.1956 0 8.45023 0 6.8918C0 5.96158 0.188643 5.05902 0.560827 4.2091C0.920255 3.3884 1.43459 2.65142 2.08981 2.01854C2.74489 1.38581 3.50797 0.88894 4.35764 0.541838C5.2375 0.182278 6.17193 0 7.13513 0C8.09805 0 9.03262 0.182278 9.91248 0.541838C10.7622 0.88894 11.5251 1.38581 12.1803 2.01854C12.8355 2.65142 13.3499 3.3884 13.7093 4.2091C14.0815 5.05902 14.2701 5.96158 14.2701 6.8918C14.2701 8.45023 13.118 11.1956 10.8462 15.0519C9.17165 17.894 7.47415 20.3272 7.45743 20.3515L7.13513 20.8125ZM7.13513 0.751222C3.62957 0.751222 0.777674 3.50586 0.777674 6.8918C0.777674 8.29773 1.92569 10.9895 4.09757 14.6764C5.32523 16.7604 6.56566 18.623 7.13513 19.4614C7.7046 18.623 8.94489 16.7604 10.1727 14.6764C12.3444 10.9895 13.4923 8.29773 13.4923 6.8918C13.4923 3.50586 10.6404 0.751222 7.13513 0.751222ZM7.13513 11.084C6.52342 11.084 5.92957 10.9682 5.37059 10.7398C4.8306 10.5192 4.34588 10.2036 3.92976 9.80161C3.51364 9.39961 3.18681 8.93143 2.95848 8.40992C2.72193 7.87 2.60203 7.29653 2.60203 6.70562C2.60203 6.1147 2.72193 5.54124 2.95848 5.00132C3.18681 4.47981 3.51364 4.01169 3.92976 3.60963C4.34588 3.20763 4.8306 2.89195 5.37059 2.67147C5.92957 2.44306 6.52342 2.32732 7.13513 2.32732C7.74684 2.32732 8.34055 2.44306 8.89953 2.67147C9.43953 2.89195 9.9241 3.20763 10.3404 3.60963C10.7565 4.01169 11.0833 4.47981 11.3115 5.00132C11.548 5.54124 11.668 6.1147 11.668 6.70562C11.668 7.29653 11.548 7.87 11.3115 8.40992C11.0833 8.93143 10.7565 9.39961 10.3404 9.80161C9.9241 10.2036 9.43953 10.5192 8.89953 10.7398C8.34055 10.9682 7.74684 11.084 7.13513 11.084ZM7.13513 3.07847C5.06431 3.07847 3.37984 4.70562 3.37984 6.70562C3.37984 8.70568 5.06431 10.3328 7.13513 10.3328C9.20581 10.3328 10.8904 8.70568 10.8904 6.70562C10.8904 4.70562 9.20581 3.07847 7.13513 3.07847Z"
                                                fill="#656565" />
                                        </svg>
                                         المدينه
                                    </span>
                                </div>
                                <span>{{ $user?->address }}</span>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- التابس + الكونتنت -->
                <div class="flex-1 w-full tabs">
                    <!-- التابس -->
                    <div class="allTabs border-b flex space-x-4 space-x-reverse mb-4">
                        <button class="profileTab  py-2 active" data-tab="account">حسابي</button>
                        <button class="profileTab  py-2" data-tab="password">تغيير كلمة السر</button>
                        {{-- <button class="profileTab  py-2" data-tab="services">الخدمات</button> --}}
                        <button class="profileTab  py-2" data-tab="booking">الحجز</button>
                    </div>
                    <!-- محتوى حسابي -->
                    <form id="profile-form" class="tab-content account login_section content active"
                        action="{{ route('web.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h2 class="text-center text-xl font-bold mb-6 largeTitle">البيانات الشخصية</h2>
                        <div class="md:flex gap-4 mb-4">
                            <input type="file" id="profile_image" name="image" accept="image/*"
                                class="hidden" onchange="previewImage(event)">
                        </div>
                        <div class="md:flex gap-4 mb-4">
                            <div class="form_group">
                                <input name="full_name" class="form_group_control" type="text"
                                    value="{{ old('full_name', $user->full_name) }}" placeholder="الاسم الكامل">
                                <!-- <label class="form_group_label">

                                    </label> -->
                            </div>
                            <div class="form_group">
                                <input name="nickname" class="form_group_control" type="text"
                                    value="{{ old('nickname', $user->nickname) }}" placeholder=" الاسم المستعار">
                                <!-- <label class="form_group_label">الاسم المستعار</label> -->
                            </div>
                        </div>
                        <div class="md:flex gap-4 mb-4">
                            <div class="form_group">
                                <input name="email" class="form_group_control" type="email"
                                    value="{{ old('email', $user->email) }}" placeholder=" البريد الإلكتروني" readonly>
                                <!-- <label class="form_group_label">البريد الإلكتروني</label> -->
                            </div>
                            <div class="form_group">
                                <input name="mobile" class="form_group_control" type="text"
                                    value="{{ old('mobile', $user->mobile) }}" placeholder=" رقم الهاتف">
                                <!-- <label class="form_group_label">رقم الهاتف</label> -->
                            </div>
                        </div>
                        <div class="md:flex gap-4 mb-4">
                            <div class="form_group">
                                <input name="address" class="form_group_control" type="text"
                                    value="{{ old('address', $user->address) }}" placeholder="العنوان ">
                                <!-- <label class="form_group_label">العنوان</label> -->
                            </div>
                        </div>
                        <div class=" flex gap-4 justify-center buttons">
                            <button class="redButton">حفظ</button>
                            <button class="close">إلغاء</button>
                        </div>
                    </form>

                    <form class="tab-content password login_section content"
                        action="{{ route('web.profile.update-password') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h2 class="text-center text-xl font-bold mb-6 largeTitle">تحديث كلمة السر</h2>
                        <div class="md:flex gap-12  mb-4">
                            <div class="form_group">
                                <input name="current_password" class="myPassword form_group_control" type="password"
                                    placeholder="كلمة السر الحالية ">
                                <!-- <label class="form_group_label">كلمة السر الحالية</label> -->
                                <span class="form_group_passwordEye" id="togglePassword" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </span>
                            </div>
                            <div class="form_group">
                                <input name="password" class="myPassword form_group_control" type="password"
                                    placeholder=" كلمة السر الجديدة">
                                <!-- <label class="form_group_label">كلمة السر الجديدة</label> -->
                                <span class="form_group_passwordEye" id="togglePassword" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="md:flex gap-4 mb-4">
                            <div class="form_group">
                                <input name="password_confirmation" class="myPassword form_group_control"
                                    type="password" placeholder=" تأكيد كلمة السر الجديدة">
                                <!-- <label class="form_group_label">تأكيد كلمة السر الجديدة</label> -->
                                <span class="form_group_passwordEye" id="togglePassword" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class=" flex gap-4 justify-center buttons">
                            <button class="redButton">حفظ</button>
                            <button class="close">إلغاء</button>
                        </div>
                    </form>
                    <!-- محتوى الخدمات -->
                    @include('web.pages.auth.partials.service-tracking')
                    <!-- محتوى الحجز -->
                    @include('web.pages.auth.partials.booking-tracking')
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
@push('scripts-js')
<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profile_image_preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            text: @json(session('success')),
            confirmButtonColor: '#d03b37',
        });
        @endif

        @if($errors -> any())
        Swal.fire({
            icon: 'error',
            text: @json(implode("\n", $errors -> all())),
            confirmButtonColor: '#d03b37',
        });
        @endif
    });
</script>
@endpush
