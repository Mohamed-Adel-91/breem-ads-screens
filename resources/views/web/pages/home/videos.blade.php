<section class="home_video_Section custom-section">
    <div class="container">
        <h2 class="custom_title text-center">
            <span> الفيديوهات </span>
        </h2>
        <div class="flex flex-wrap  w-full">
            @if ($last_video)
            <div class="w-full px-2 mb-4">
                <a href="#" class="home_video_Section_cardvideo" data-video-url="{{ $last_video->link }}">
                    <div class="home_video_Section_cardvideo_header">
                        <span class="w-full h-full">
                            <img class="w-full h-full object-contain" src="{{ $last_video->thumb_image_path ?: $last_video->thumbnail_url }}"
                                alt="{{ $last_video->title }}" data-ytid="{{ $last_video->youtube_id }}"
                                onerror="ytThumbFallback(this)">
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
                            <img class="w-full h-full object-contain" src="{{ $item->thumb_image_path ?: $item->thumbnail_url }}"
                                alt="{{ $item->title }}" data-ytid="{{ $item->youtube_id }}"
                                onerror="ytThumbFallback(this)">

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
<div class=" w-full py-6 md:block hidden">
    <!-- Advertisement Label -->

    <!-- Centered Image -->
    <div class="largeImg">
        <p class="text-xs text-gray-400 text-right">
            ADVERTISEMENT
        </p>

        <!-- footer_banner [async] -->
        <script type="text/javascript">if (!window.AdButler){(function(){var s = document.createElement("script"); s.async = true; s.type = "text/javascript"; s.src = 'https://servedby.adfyre.co/app.js'; var n = document.getElementsByTagName("script")[0]; n.parentNode.insertBefore(s, n);}());}</script>
        <script type="text/javascript">
        var AdButler = AdButler || {};
        AdButler.ads = AdButler.ads || [];
        var abkw = window.abkw || '';
        var plc994383 = window.plc994383 || 0;
        document.write('<'+'div id="placement_994383_'+plc994383+'"></'+'div>');
        AdButler.ads.push({handler: function(opt){ AdButler.register(188745, 994383, [970,250], 'placement_994383_'+opt.place, opt); }, opt: { place: plc994383++, keywords: abkw, domain: 'servedby.adfyre.co', click: 'CLICK_MACRO_PLACEHOLDER' }});
        </script>
    </div>
</div>
<div class=" w-full py-6 md:hidden block">
    <!-- Advertisement Label -->

    <!-- Centered Image -->
    <div class="largeImg">
        <p class="text-xs text-gray-400 text-right">
            ADVERTISEMENT
        </p>

        <!-- <img src="img/ads/1.png" alt="Ad Image" class="max-w-full h-auto" /> -->
        <!-- internal_showcase [async] 300X300-->
        <script type="text/javascript">
            if (!window.AdButler) {
                (function() {
                    var s = document.createElement("script");
                    s.async = true;
                    s.type = "text/javascript";
                    s.src = 'https://servedby.adfyre.co/app.js';
                    var n = document.getElementsByTagName("script")[0];
                    n.parentNode.insertBefore(s, n);
                }());
            }
        </script>
        <script type="text/javascript">
            var AdButler = AdButler || {};
            AdButler.ads = AdButler.ads || [];
            var abkw = window.abkw || '';
            var plc994384 = window.plc994384 || 0;
            document.write('<' + 'div id="placement_994384_' + plc994384 + '"></' + 'div>');
            AdButler.ads.push({
                handler: function(opt) {
                    AdButler.register(188745, 994384, [300, 300], 'placement_994384_' + opt.place, opt);
                },
                opt: {
                    place: plc994384++,
                    keywords: abkw,
                    domain: 'servedby.adfyre.co',
                    click: 'CLICK_MACRO_PLACEHOLDER'
                }
            });
        </script>
    </div>
</div>
@push('scripts-js')
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
