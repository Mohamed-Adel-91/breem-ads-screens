<section class="banner">
    <div class="banner_video">
        <video src="{{ $banner['video_url'] ? asset($banner['video_url']) : '' }}"
            @if ($banner['autoplay']) autoplay @endif @if ($banner['loop']) loop @endif
            @if ($banner['playsinline']) playsinline @endif @if ($banner['muted']) muted @endif
            @if ($banner['controls']) controls @endif></video>
    </div>
</section>
