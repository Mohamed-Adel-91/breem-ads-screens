{{--
    The inner-page header: opaque white with teal links, used on every page that has no
    hero video behind its navigation.

    master.blade.php still chooses between this file and transparent-header, so the
    include contract either side of it is unchanged. The markup itself lives in one
    shared partial — see components/navbar.blade.php for why.
--}}
@include('web.layouts.components.navbar', ['solid' => true])
