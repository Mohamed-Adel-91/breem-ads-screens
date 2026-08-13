{{--
    The home header: transparent, sitting over the hero video, with white links and a
    dark scrim behind them (see the `header` rule in master.css).

    master.blade.php still chooses between this file and solid-header, so the include
    contract either side of it is unchanged. The markup itself lives in one shared
    partial — see components/navbar.blade.php for why.
--}}
@include('web.layouts.components.navbar', ['solid' => false])
