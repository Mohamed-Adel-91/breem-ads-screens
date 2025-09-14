<section class="Knowmore">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-3">
                <h3>{{ $knowMore['title'] }}</h3>
            </div>
            <div class="col-12 col-md-9">
                <p class="desc">{{ $knowMore['desc'] }}</p>
                <a href="{{ $knowMore['readmore_link'] }}" class="link_button">
                    {{ __('اقرأ المزيد') }}
                </a>
            </div>
        </div>
    </div>
</section>
