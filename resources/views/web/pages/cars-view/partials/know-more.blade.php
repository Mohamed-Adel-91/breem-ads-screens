<div class="container">
    <div class="Tobtitle">
        <h2>أعرف أكتر</h2>
    </div>
    <div class="flex-div">
        <div class="w-full lg:w-2/3">
            <div class="video-container">
                <img class="w-full"
                    src="{{ $car->knowMoreHeroImage?->hero_image_path ?? asset('img/homepage/video.png') }}"
                    alt="{{ $car->knowMoreHeroImage?->title ?? '' }}">
                @if ($car->knowMoreHeroVideo)
                    <button class="play-button" onclick="openVideoPopup('{{ $heroVideoSrc }}')">
                        <img src="img/homepage/play.png" alt="Play Image">
                    </button>
                @endif
            </div>
        </div>
        <div class="w-full lg:w-1/3 bg-gray-100 content-center section_desc">
            <h2 class="px-4 mb-4 mt-4 lg:mt-0">{{ $car->knowMoreHeroImage?->title ?? '' }}</h2>
            <p class="px-4">{{ $car->knowMoreHeroImage?->description ?? '' }}</p>
        </div>
        <div class="flex flex-col lg:flex-row gap-2 mt-2 w-full">
            @foreach ($knowMoreNonHero as $item)
                <div class="w-full lg:w-1/3 bg-gray-100 content-center section_desc">
                    <div class="video-container">
                        <img class="w-full" src="{{ $item->image_path }}" alt="{{ $item->title }}">
                        @if (!empty($knowMoreEmbedUrls[$item->id]))
                            <button class="play-button" onclick="openVideoPopup('{{ $knowMoreEmbedUrls[$item->id] }}')">
                                <img src="img/homepage/play.png" alt="Play Image">
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@push('scripts-js')
    <script>
        function openVideoPopup(src) {
            if (!src) return;
            const videoPopup = document.getElementById('videoPopup');
            const videoIframe = document.getElementById('videoIframe');
            videoIframe.src = src;
            videoPopup.style.display = 'flex';
        }

        function closeVideoPopup() {
            const videoPopup = document.getElementById('videoPopup');
            const videoIframe = document.getElementById('videoIframe');
            videoPopup.style.display = 'none';
            videoIframe.src = '';
        }
    </script>
@endpush
