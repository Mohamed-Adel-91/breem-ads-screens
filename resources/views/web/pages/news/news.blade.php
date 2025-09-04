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
    <meta property="og:image"
        content="https://dev-iconcreations.com/el-tawkeel/public/frontend/img/homepage/og-image.png" />
    <meta property="og:url" content="https://eltawkeel.com/" />
    <meta property="og:type" content="website" />
@endpush

@section('content')
    <!-- Main Content -->
    <main>

        <section class="custom-banner">
            <span>
                <img src="img/homepage/news.jpg" alt="banner 3" class="image lg:block hidden" />
                <img src="img/homepage/tablet5.png" alt="banner 3" class="image lg:hidden md:block hidden" />
                <img src="img/homepage/mobile5.png" alt="banner 3" class="image md:hidden block" />
            </span>
        </section>

        <section class="custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>أخر الاخبار</span>
                </h2>

                <a href="{{ route('web.news.details', [$last_news->id, \unicode_slug($last_news->title, '-')]) }}">
                <div class="topsection_News  items-center shadow-md rounded-md">
                        <div class=" order-2 md:order-1 md:w-1/2 ">
                            <div class="w-11/12">
                                <h4 class="text-lg mb-6">{{ $last_news->title }}</h4>
                                <p>{{ $last_news->short_desc }}</p>
                            </div>
                        </div>
                        <div class=" order-1 md:order-2 md:w-1/2 md:mb-0 mb-4">
                            <div class="img-full imagenews">
                                <img src="{{ $last_news->thumb_url }}" alt="{{ $last_news->altText }}">
                            </div>
                            
                        </div>
                    </div>
                </a>    
            </div>
        </section>

        <section class="custom-section bottomsection_news">
            <div class="container">
                <h2 class="custom_title">
                    <span> الأخبار</span>
                </h2>

                <div class="flex flex-wrap  w-full">
                    @foreach ($news as $item)
                        <div class="w-full md:w-1/2 mb-4 lg:mb-0 lg:w-1/3 px-2 pt-2">
                            <a href="{{ route('web.news.details', [$item->id, \unicode_slug($item->title, '-')]) }}"
                                class="News_card zoomIn">
                                <div class="News_card_header">
                                    <span>
                                        <img class="w-full h-full object-contain" src="{{ $item->thumb_url }}"
                                            alt="{{ $item->altText }}">
                                    </span>
                                </div>
                                <div class="News_card_body">
                                    <h5 class=" mb-3">{{ $item->title }}</h5>
                                    <p class="text-gray-500">
                                        {{ $item->short_desc }}
                                    </p>
                                    <button class="flex items-center gap-3 mt-3 transition-all mb-0 hover:gap-5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19.534" height="10.154"
                                            viewBox="0 0 19.534 10.154">
                                            <path id="Path_7658" data-name="Path 7658"
                                                d="M840.759,355.741a11.933,11.933,0,0,0-19.534,0,11.933,11.933,0,0,0,19.534,0Zm-9.767-2.539a2.539,2.539,0,1,1-2.538,2.539,2.539,2.539,0,0,1,2.538-2.539Zm0,1.338a1.2,1.2,0,1,1-1.2,1.2,1.2,1.2,0,0,1,1.2-1.2Zm7.493,1.2a10.156,10.156,0,0,0-14.985,0,10.156,10.156,0,0,0,14.985,0Z"
                                                transform="translate(-821.225 -350.664)" fill="#d03b37" fill-rule="evenodd">
                                            </path>
                                        </svg>
                                        <span>لمعرفة المزيد</span>
                                    </button>
                                </div>
                            </a>
                        </div>
                    @endforeach
                    @if ($news->hasPages())
                        <div class="mt-6 space-y-3 w-fit mx-auto text-center">
                            {{ $news->onEachSide(1)->appends(request()->query())->links('web.layouts.partials.pagination', ['paginator' => $news]) }}
                        </div>
                    @endif
                </div>
            </div>
        </section>

    </main>
@endsection

@push('scripts-js')
    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('open');
            }
        }

        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                document.querySelector('.sidebar')?.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }
    </script>
@endpush
