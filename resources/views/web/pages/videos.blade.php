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
        <section class="home_video_Section custom-section">
            <div class="container">
                <h2 class="custom_title text-center">
                    <span>
                        الفيديوهات
                    </span>
                </h2>
                <div class="flex flex-wrap  w-full">
                    @if ($last_video)
                        <div class="w-full px-2 mb-4">
                            <a href="#" class="home_video_Section_cardvideo" data-video-url="{{ $last_video->link }}">
                                <div class="home_video_Section_cardvideo_header">
                                    <span class="w-full h-full">
                                        <img class="w-full h-full object-contain"
                                            src="{{ $last_video->thumb_image_path ?: $last_video->thumbnail_url }}"
                                            alt="{{ $last_video->title }}" data-ytid="{{ $last_video->youtube_id }}"
                                            onerror="ytThumbFallback(this)" loading="lazy">
                                    </span>
                                    <div class="home_video_Section_cardvideo_header_play">
                                        <span>
                                            <img src="img/homepage/play.png" alt="play image" />
                                        </span>
                                    </div>
                                </div>

                                <div class="home_video_Section_cardvideo_body">
                                    <h5>{{ $last_video->title }}</h5>
                                    <button class="flex items-center gap-3 mb-0 hover:gap-5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19.534" height="10.154"
                                            viewBox="0 0 19.534 10.154">
                                            <path id="Path_7658" data-name="Path 7658"
                                                d="M840.759,355.741a11.933,11.933,0,0,0-19.534,0,11.933,11.933,0,0,0,19.534,0Zm-9.767-2.539a2.539,2.539,0,1,1-2.538,2.539,2.539,2.539,0,0,1,2.538-2.539Zm0,1.338a1.2,1.2,0,1,1-1.2,1.2,1.2,1.2,0,0,1,1.2-1.2Zm7.493,1.2a10.156,10.156,0,0,0-14.985,0,10.156,10.156,0,0,0,14.985,0Z"
                                                transform="translate(-821.225 -350.664)" fill="#d03b37" fill-rule="evenodd">
                                            </path>
                                        </svg><span>شاهد الفيديو</span>
                                    </button>
                                </div>
                            </a>
                        </div>
                    @endif
                    @foreach ($videos as $item)
                        <div class="px-4 w-full md:w-1/2 lg:w-2/6 mb-4">
                            <a class="home_video_Section_smallcardvideo" data-video-url="{{ $item->link }}">
                                <div class="w-full home_video_Section_smallcardvideo_header">
                                    <span class="w-full h-full">
                                        <img class="w-full h-full object-contain" src="{{$item->thumb_image_path ?: $item->thumbnail_url }}"
                                            alt="{{ $item->title }}" data-ytid="{{ $item->youtube_id }}"
                                            onerror="ytThumbFallback(this)"  loading="lazy">

                                    </span>
                                    <div class="home_video_Section_cardvideo_header_play">
                                        <span>
                                            <img src="img/homepage/play.png" alt="play image" />
                                        </span>
                                    </div>
                                </div>
                                <div class="img-full home_video_Section_smallcardvideo_body">
                                    <h5>{{ $item->title }}</h5>
                                    <button class="flex items-center gap-3 mb-0 hover:gap-5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19.534" height="10.154"
                                            viewBox="0 0 19.534 10.154">
                                            <path id="Path_7658" data-name="Path 7658"
                                                d="M840.759,355.741a11.933,11.933,0,0,0-19.534,0,11.933,11.933,0,0,0,19.534,0Zm-9.767-2.539a2.539,2.539,0,1,1-2.538,2.539,2.539,2.539,0,0,1,2.538-2.539Zm0,1.338a1.2,1.2,0,1,1-1.2,1.2,1.2,1.2,0,0,1,1.2-1.2Zm7.493,1.2a10.156,10.156,0,0,0-14.985,0,10.156,10.156,0,0,0,14.985,0Z"
                                                transform="translate(-821.225 -350.664)" fill="#d03b37" fill-rule="evenodd">
                                            </path>
                                        </svg><span>شاهد الفيديو</span>
                                    </button>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                @if ($videos->hasPages())
                    <div class="mt-6 space-y-3 w-fit mx-auto text-center">
                        {{ $videos->onEachSide(1)->appends(request()->query())->links('web.layouts.partials.pagination', ['paginator' => $videos]) }}
                    </div>
                @endif
            </div>

            <div class="popupsection" id="videoPopup">
                <div class="popupsection_close" id="closeButton">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="popupsection_box">
                    <iframe id="videoIframe" width="100%" height="100%" src="" title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                    </iframe>
                </div>
            </div>
        </section>

    </main>
@endsection

@push('scripts-js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoCards = document.querySelectorAll(
                '.home_video_Section_cardvideo, .home_video_Section_smallcardvideo');
            const popup = document.getElementById('videoPopup');
            const closeBtn = document.getElementById('closeButton');
            const iframe = document.getElementById('videoIframe');
            videoCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    const videoUrl = this.getAttribute('data-video-url');
                    if (videoUrl) {
                        iframe.src = videoUrl;
                        popup.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                });
            });
            closeBtn.addEventListener('click', function() {
                closePopup();
            });
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    closePopup();
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && popup.classList.contains('active')) {
                    closePopup();
                }
            });

            function closePopup() {
                popup.classList.remove('active');
                iframe.src = '';
                document.body.style.overflow = 'auto';
            }
        });
    </script>
    <script>
        function getYouTubeId(url) {
            try {
                const u = new URL(url);
                const host = u.hostname.replace(/^www\./, '');
                if (host === 'youtube.com' || host === 'm.youtube.com') {
                    if (u.pathname.startsWith('/watch')) return u.searchParams.get('v');
                    if (u.pathname.startsWith('/shorts/')) return u.pathname.split('/')[2];
                    if (u.pathname.startsWith('/embed/')) return u.pathname.split('/')[2];
                }
                if (host === 'youtu.be') {
                    return u.pathname.split('/')[1];
                }
            } catch (_) {}
            return null;
        }

        function toEmbedUrl(url) {
            const id = getYouTubeId(url);
            return id ? `https://www.youtube.com/embed/${id}?autoplay=1&rel=0&modestbranding=1` : null;
        }

        document.addEventListener('click', function(e) {
            const a = e.target.closest('.home_video_Section_cardvideo, .home_video_Section_smallcardvideo');
            if (!a) return;

            e.preventDefault();
            const raw = a.dataset.videoUrl;
            const embed = toEmbedUrl(raw);
            if (!embed) {
                console.warn('Not a valid YouTube link:', raw);
                return;
            }

            const iframe = document.getElementById('videoIframe');
            iframe.src = embed;
            document.getElementById('videoPopup').classList.add(
                'open'); // make sure your CSS shows the popup on .open
        });

        document.getElementById('closeButton').addEventListener('click', function() {
            const iframe = document.getElementById('videoIframe');
            iframe.src = ''; // stop playback
            document.getElementById('videoPopup').classList.remove('open');
        });
    </script>
@endpush
