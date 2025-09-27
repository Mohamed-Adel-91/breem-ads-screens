<!-- Sidebar wrapper start -->
<nav id="sidebar" class="sidebar-wrapper">
    <!-- Sidebar brand start  -->
    <div class="sidebar-brand d-flex justify-content-center" style="height: 100px; margin-bottom: 10px;">
        <a href="{{ route('admin.dashboard', ['lang' => app()->getLocale()]) }}">
            <img src="{{ asset('frontend/img/logo.png') }}"
                style="width: 100%; padding:20px; margin-top: 10px; margin-bottom: 10px;">
        </a>
    </div>
    <!-- Sidebar brand end  -->
    <!-- Sidebar content start -->
    <div class="sidebar-content">
        <!-- sidebar menu start -->
        <div class="sidebar-menu">
            @php
                $menuItems = app(\App\Services\Admin\MenuBuilder::class)->build('sidebar');
            @endphp

            <x-admin.menu :items="$menuItems" variant="sidebar" />
        </div>
        <!-- sidebar menu end -->
    </div>
    <!-- Sidebar content end -->
</nav>
<!-- Sidebar wrapper end -->
@push('custom-css-scripts')
    <style>
        .sidebar-content {
            max-height: calc(100vh - 70px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-content::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 4px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }

        .sidebar-menu ul li.open>a {
            background: #edf3ff;
        }

        .sidebar-menu ul li.open>a i {
            color: #41A8A6;
        }

        .sidebar-menu ul li.open .sidebar-submenu,
        .sidebar-menu .sidebar-submenu.open {
            display: block;
        }

        .sidebar-menu .sidebar-dropdown.open>a:after {
            transform: rotate(-180deg);
        }
    </style>
@endpush



