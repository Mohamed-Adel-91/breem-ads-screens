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
            <ul>
                <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard', ['lang' => app()->getLocale()]) }}">
                        <i class="icon-home"></i>
                        <span class="menu-text">{{ __('admin.sidebar.dashboard') }}</span>
                    </a>
                </li>

                @canany(['ads.view', 'ads.create', 'ads.edit'])
                    <li class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.ads.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-playlist_add_check"></i>
                            <span class="menu-text">{{ __('admin.sidebar.ads') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['screens.view', 'screens.create', 'screens.edit'])
                    <li class="{{ request()->routeIs('admin.screens.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.screens.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-layout"></i>
                            <span class="menu-text">{{ __('admin.sidebar.screens') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['places.view', 'places.create', 'places.edit'])
                    <li class="{{ request()->routeIs('admin.places.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.places.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-pin"></i>
                            <span class="menu-text">{{ __('admin.sidebar.places') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['monitoring.view', 'monitoring.manage'])
                    <li class="{{ request()->routeIs('admin.monitoring.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.monitoring.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-activity"></i>
                            <span class="menu-text">{{ __('admin.sidebar.monitoring') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['reports.view', 'reports.generate'])
                    <li class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-pie-chart1"></i>
                            <span class="menu-text">{{ __('admin.sidebar.reports') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['logs.view', 'logs.export'])
                    <li class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.logs.index', ['lang' => app()->getLocale()]) }}">
                            <i class="icon-clipboard"></i>
                            <span class="menu-text">{{ __('admin.sidebar.logs') }}</span>
                        </a>
                    </li>
                @endcanany

                @canany(['admins.view', 'admins.create', 'admins.edit', 'permissions.view', 'roles.view'])
                    <li
                        class="sidebar-dropdown {{ request()->routeIs('admin.admins.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-user-shield" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.admins_management') }}</span>
                        </a>
                        <div class="sidebar-submenu">
                            <ul>
                                @canany(['admins.view', 'admins.create', 'admins.edit'])
                                    <li class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.admins.index', ['lang' => app()->getLocale()]) }}"
                                            class="{{ request()->routeIs('admin.admins.*') ? 'current-page' : '' }}">
                                            {{ __('admin.sidebar.admins') }}
                                        </a>
                                    </li>
                                @endcanany
                                @role('super-admin')
                                    <li class="{{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.permissions.index', ['lang' => app()->getLocale()]) }}"
                                            class="{{ request()->routeIs('admin.permissions.*') ? 'current-page' : '' }}">
                                            {{ __('admin.sidebar.permissions') }}
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                        <a href="{{ route('admin.roles.index', ['lang' => app()->getLocale()]) }}"
                                            class="{{ request()->routeIs('admin.roles.*') ? 'current-page' : '' }}">
                                            {{ __('admin.sidebar.roles') }}
                                        </a>
                                    </li>
                                @endrole
                            </ul>
                        </div>
                    </li>
                    <li
                        class="sidebar-dropdown {{ Request::is('dashboard/users') || Request::is('dashboard/users/show') ? 'active' : '' }}">
                        <a href="#">
                            <i><i class="fas fa-user-cog" aria-hidden="true"></i></i>
                            <span class="menu-text">{{ __('admin.sidebar.users_management') }}</span>
                        </a>
                        <div class="sidebar-submenu">
                            <ul>
                                <li class="{{ Request::is('dashboard/users') ? 'active' : '' }}">
                                    <a href="{{ route('admin.users.index', ['lang' => app()->getLocale()]) }}"
                                        class="{{ Request::is('dashboard/users') ? 'current-page' : '' }}">{{ __('admin.sidebar.show') }}</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li
                        class="sidebar-dropdown {{ Request::is('dashboard/contact-submissions*') || Request::is('dashboard/cms/home/edit') || Request::is('dashboard/cms/whoweare/edit') || Request::is('dashboard/cms/contact-us/edit') || Request::is('*admin-panel/cms/home/*') || Request::is('*admin-panel/cms/whoweare/*') || Request::is('*admin-panel/cms/contact-us/*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.contact_submissions') }}</span>
                        </a>
                        <div class="sidebar-submenu">
                            <ul>
                                <li class="{{ Request::is('dashboard/contact-submissions') ? 'active' : '' }}">
                                    <a href="{{ route('admin.contact_submissions.index', ['lang' => app()->getLocale()]) }}"
                                        class="{{ Request::is('dashboard/contact-submissions') ? 'current-page' : '' }}">{{ __('admin.sidebar.all_submissions') }}</a>
                                </li>
                                <li
                                    class="{{ Request::is('dashboard/cms/home/edit') || Request::is('*admin-panel/cms/home/edit') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.home.edit', ['lang' => app()->getLocale()]) }}"
                                        class="{{ Request::is('dashboard/cms/home/edit') || Request::is('*admin-panel/cms/home/edit') ? 'current-page' : '' }}">{{ __('admin.sidebar.home_page') }}</a>
                                </li>
                                <li
                                    class="{{ Request::is('dashboard/cms/whoweare/edit') || Request::is('*admin-panel/cms/whoweare/edit') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.whoweare.edit', ['lang' => app()->getLocale()]) }}"
                                        class="{{ Request::is('dashboard/cms/whoweare/edit') || Request::is('*admin-panel/cms/whoweare/edit') ? 'current-page' : '' }}">{{ __('admin.sidebar.who_we_are') }}</a>
                                </li>
                                <li
                                    class="{{ Request::is('dashboard/cms/contact-us/edit') || Request::is('*admin-panel/cms/contact-us/edit') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.contact.edit', ['lang' => app()->getLocale()]) }}"
                                        class="{{ Request::is('dashboard/cms/contact-us/edit') || Request::is('*admin-panel/cms/contact-us/edit') ? 'current-page' : '' }}">{{ __('admin.sidebar.contact_us') }}</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endcanany
            </ul>
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
    </style>
@endpush
