<!-- Desktop Header -->
<header class="hidden lg:block bg-white shadow-md">
    <nav class="relative">
        <div class="container">
            <div class="flex items-center">
                <a class="ml-3 block" href="{{ route('web.home') }}">
                    <img src="img/homepage/logo-new.png" alt="التوكيل" class="h-12 w-auto" />
                </a>

                <div class="flex items-center justify-between flex-auto">
                    <ul class="list-none flex items-center justify-between gap-6">
                        <li>
                            <a href="{{ route('web.home') }}" class='nav-item link_li'>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="w-5 inline-block ml-1">
                                    <path
                                        d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                            </a>
                        </li>
                        <li><a href="{{ route('web.new-cars') }}" class='nav-item link_li'>السيارات الجديدة</a></li>
                        <li><a href="{{ route('web.comparison') }}" class='nav-item link_li'>مقارنات السيارات</a></li>
                        <li><a href="{{ route('web.news') }}" class='nav-item link_li'>أخبار السيارات</a></li>
                        <li><a href="{{ route('web.videos') }}" class='nav-item link_li'>فيديوهات</a></li>
                        <li><a href="{{ route('web.installment') }}" class='nav-item link_li'>التقسيط</a></li>
                        <li><a href="{{ route('web.insurance') }}" class='nav-item link_li'>التأمين</a></li>
                        <li><a href="{{ route('web.service-centers') }}" class='nav-item link_li'>مراكز الخدمة</a></li>
                    </ul>

                    <div id="authWrapper" class="flex items-center gap-2">
                        @guest('user')
                            <a href="{{ route('web.login') }}" class="redButton">
                                <span>تسجيل دخول</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </a>
                        @endguest
                        @auth('user')
                            @php $user = auth('user')->user(); @endphp
                            <div class="auth-user items-center gap-4">
                                <a href="{{ route('web.profile') }}" class="flex items-center gap-4">
                                    <h4 class="auth-name">{{ $user->nickname ?? 'مرحبا بك' }}</h4>

                                    <img class="auth-avatar"
                                        src="{{ $user?->image_path ?? asset('frontend/img/profile/user.png') }}"
                                        alt="User Avatar">
                                </a>
                            </div>
                            <a href="#" id="logoutBtn" class="redButton">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25" fill="none">
                                    <path d="M13.25 22.25H19.25C20.9075 22.25 22.25 20.9075 22.25 19.25V5.75C22.25 4.0925 20.9075 2.75 19.25 2.75H13.25C11.5925 2.75 10.25 4.0925 10.25 5.75V8C10.25 8.4125 10.5875 8.75 11 8.75C11.4125 8.75 11.75 8.4125 11.75 8V5.75C11.75 4.925 12.425 4.25 13.25 4.25H19.25C20.075 4.25 20.75 4.925 20.75 5.75V19.25C20.75 20.075 20.075 20.75 19.25 20.75H13.25C12.425 20.75 11.75 20.075 11.75 19.25V17C11.75 16.5875 11.4125 16.25 11 16.25C10.5875 16.25 10.25 16.5875 10.25 17V19.25C10.25 20.9075 11.5925 22.25 13.25 22.25Z" fill="white"/>
                                    <path d="M2.9675 11.969L6.7175 8.21902C6.78694 8.14958 6.86937 8.0945 6.96009 8.05692C7.05082 8.01934 7.14805 8 7.24625 8C7.34445 8 7.44168 8.01934 7.53241 8.05692C7.62313 8.0945 7.70556 8.14958 7.775 8.21902C7.84444 8.28845 7.89952 8.37089 7.93709 8.46161C7.97467 8.55233 7.99401 8.64957 7.99401 8.74777C7.99401 8.84596 7.97467 8.9432 7.93709 9.03392C7.89952 9.12465 7.84444 9.20708 7.775 9.27652L5.3075 11.744H15.5C15.9125 11.744 16.25 12.0815 16.25 12.494C16.25 12.9065 15.9125 13.244 15.5 13.244H5.3075L7.775 15.7115C7.87966 15.8163 7.95083 15.9497 7.97948 16.095C8.00813 16.2403 7.99296 16.3908 7.93589 16.5274C7.87883 16.664 7.78245 16.7806 7.65899 16.8624C7.53553 16.9441 7.39056 16.9873 7.2425 16.9865C7.0475 16.9865 6.86 16.9115 6.71 16.769L2.96 13.019C2.89047 12.9496 2.83531 12.8672 2.79768 12.7765C2.76004 12.6858 2.74067 12.5885 2.74067 12.4903C2.74067 12.392 2.76004 12.2948 2.79768 12.204C2.83531 12.1133 2.89047 12.0309 2.96 11.9615L2.9675 11.969Z" fill="white"/>
                                </svg>
                            </a>

                            <form id="logout-form" action="{{ route('web.logout') }}" method="POST" class="hidden" enctype="multipart/form-data">
                                @csrf
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

@push('scripts-js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'هل أنت متأكد من تسجيل الخروج؟',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم',
                        confirmButtonColor: '#d03b37',
                        cancelButtonText: 'لا'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('logout-form').submit();
                        }
                    });
                });
            }
        });
    </script>
@endpush
