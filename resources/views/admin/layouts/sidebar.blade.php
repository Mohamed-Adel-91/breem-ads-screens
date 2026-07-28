@php
    $menuItems = app(\App\Services\Admin\MenuBuilder::class)->build('static-sidebar');
@endphp

<aside class="sidebar-left border-right bg-white shadow"
       id="leftSidebar"
       aria-label="{{ __('admin.header.panel_title') }}"
       data-simplebar>
    <button type="button"
            class="btn collapseSidebar toggle-btn d-lg-none text-muted mt-3"
            aria-label="{{ __('admin.pages.dashboard.title') }}"
            aria-controls="leftSidebar"
            aria-expanded="false">
        <i class="fe fe-x" aria-hidden="true"></i>
    </button>

    <nav class="vertnav navbar navbar-light">
        <div class="sidebar-brand w-100 d-flex align-items-center justify-content-center">
            <a href="{{ route('admin.dashboard', ['lang' => app()->getLocale()]) }}">
                <img src="{{ asset('admin-assets/assets/images/breem-logo.png') }}"
                     alt="Breem">
            </a>
        </div>

        <x-admin.menu :items="$menuItems" variant="static-sidebar" />
    </nav>
</aside>
