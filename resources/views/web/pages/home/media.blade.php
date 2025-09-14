<section class="media">
    <div class="container">
        <div class="row">
            @foreach ($mediaStats as $s)
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="box">
                        <div class="image">
                            <img class="w-100" src="{{ asset($s['icon_url']) }}" alt="">
                        </div>
                        <div><span>{{ $s['number'] }}</span></div>
                        <div class="desc">
                            <p>{{ $s['label'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
