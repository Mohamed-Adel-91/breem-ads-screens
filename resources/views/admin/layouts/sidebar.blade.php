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
                @php($locale = app()->getLocale())

                @php($dashboardNav = nav_active('admin.dashboard'))
                <li @class(['active' => $dashboardNav['is_active']])>
                    <a href="{{ route('admin.dashboard', ['lang' => $locale]) }}">
                        <i class="icon-home"></i>
                        <span class="menu-text">{{ __('admin.sidebar.dashboard') }}</span>
                    </a>
                </li>

                @canany([
                    'ads.view',
                    'ads.create',
                    'ads.edit',
                    'ads.delete',
                    'ads.schedule',
                    'screens.view',
                    'screens.create',
                    'screens.edit',
                    'screens.delete',
                    'places.view',
                    'places.create',
                    'places.edit',
                    'places.delete',
                    'monitoring.view',
                    'monitoring.manage',
                    'reports.view',
                    'reports.generate',
                    'logs.view',
                    'logs.export',
                ])
                    @php($adsSystemNav = nav_active([
                        'admin.ads.*',
                        'admin.screens.*',
                        'admin.places.*',
                        'admin.monitoring.*',
                        'admin.reports.*',
                        'admin.logs.*',
                    ]))
                    <li @class(['sidebar-dropdown', 'active' => $adsSystemNav['is_active'], 'open' => $adsSystemNav['is_active']])>
                        <a href="#" @class(['active' => $adsSystemNav['is_active']])>
                            <i class="icon-playlist_add_check"></i>
                            <span class="menu-text">{{ __('admin.sidebar.ads_system') }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $adsSystemNav['is_active']])>
                            <ul>
                                @canany(['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.schedule'])
                                    @php($allAdsNav = nav_active('admin.ads.*'))
                                    <li @class(['active' => $allAdsNav['is_active']])>
                                        <a href="{{ route('admin.ads.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $allAdsNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_all_ads') }}
                                        </a>
                                    </li>
                                @endcanany

                                @can('ads.view')
                                    @php($schedulesNav = nav_active('admin.ads.schedules.*'))
                                    <li @class(['active' => $schedulesNav['is_active']])>
                                        <a href="{{ route('admin.ads.index', ['lang' => $locale, 'tab' => 'schedules']) }}"
                                           @class(['current-page' => $schedulesNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_schedules') }}
                                        </a>
                                    </li>
                                @endcan

                                @canany(['screens.view', 'screens.create', 'screens.edit', 'screens.delete'])
                                    @php($adsScreensNav = nav_active('admin.screens.*'))
                                    <li @class(['active' => $adsScreensNav['is_active']])>
                                        <a href="{{ route('admin.screens.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adsScreensNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_screens') }}
                                        </a>
                                    </li>
                                @endcanany

                                @canany(['places.view', 'places.create', 'places.edit', 'places.delete'])
                                    @php($adsPlacesNav = nav_active('admin.places.*'))
                                    <li @class(['active' => $adsPlacesNav['is_active']])>
                                        <a href="{{ route('admin.places.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adsPlacesNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_places') }}
                                        </a>
                                    </li>
                                @endcanany

                                @canany(['monitoring.view', 'monitoring.manage'])
                                    @php($adsMonitoringNav = nav_active('admin.monitoring.*'))
                                    <li @class(['active' => $adsMonitoringNav['is_active']])>
                                        <a href="{{ route('admin.monitoring.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adsMonitoringNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_monitoring') }}
                                        </a>
                                    </li>
                                @endcanany

                                @canany(['reports.view', 'reports.generate'])
                                    @php($adsReportsNav = nav_active('admin.reports.*'))
                                    <li @class(['active' => $adsReportsNav['is_active']])>
                                        <a href="{{ route('admin.reports.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adsReportsNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_reports') }}
                                        </a>
                                    </li>
                                @endcanany

                                @canany(['logs.view', 'logs.export'])
                                    @php($adsLogsNav = nav_active('admin.logs.*'))
                                    <li @class(['active' => $adsLogsNav['is_active']])>
                                        <a href="{{ route('admin.logs.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adsLogsNav['is_active']])>
                                            {{ __('admin.sidebar.ads_system_logs') }}
                                        </a>
                                    </li>
                                @endcanany
                            </ul>
                        </div>
                    </li>
                @endcanany

                @canany(['admins.view', 'admins.create', 'admins.edit', 'admins.delete', 'permissions.view', 'roles.view'])
                    @php($adminManagementNav = nav_active(['admin.admins.*', 'admin.permissions.*', 'admin.roles.*']))
                    <li @class(['sidebar-dropdown', 'active' => $adminManagementNav['is_active'], 'open' => $adminManagementNav['is_active']])>
                        <a href="#" @class(['active' => $adminManagementNav['is_active']])>
                            <i class="fas fa-user-shield" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.admins_management') }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $adminManagementNav['is_active']])>
                            <ul>
                                @canany(['admins.view', 'admins.create', 'admins.edit', 'admins.delete'])
                                    @php($adminsNav = nav_active('admin.admins.*'))
                                    <li @class(['active' => $adminsNav['is_active']])>
                                        <a href="{{ route('admin.admins.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $adminsNav['is_active']])>
                                            {{ __('admin.sidebar.admins') }}
                                        </a>
                                    </li>
                                @endcanany
                                @can('permissions.view')
                                    @php($permissionsNav = nav_active('admin.permissions.*'))
                                    <li @class(['active' => $permissionsNav['is_active']])>
                                        <a href="{{ route('admin.permissions.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $permissionsNav['is_active']])>
                                            {{ __('admin.sidebar.permissions') }}
                                        </a>
                                    </li>
                                @endcan
                                @can('roles.view')
                                    @php($rolesNav = nav_active('admin.roles.*'))
                                    <li @class(['active' => $rolesNav['is_active']])>
                                        <a href="{{ route('admin.roles.index', ['lang' => $locale]) }}"
                                           @class(['current-page' => $rolesNav['is_active']])>
                                            {{ __('admin.sidebar.roles') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                @can('users.view')
                    @php($usersNav = nav_active('admin.users.*'))
                    <li @class(['sidebar-dropdown', 'active' => $usersNav['is_active'], 'open' => $usersNav['is_active']])>
                        <a href="#" @class(['active' => $usersNav['is_active']])>
                            <i class="fas fa-user-cog" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.users_management') }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $usersNav['is_active']])>
                            <ul>
                                <li @class(['active' => $usersNav['is_active']])>
                                    <a href="{{ route('admin.users.index', ['lang' => $locale]) }}"
                                       @class(['current-page' => $usersNav['is_active']])>
                                        {{ __('admin.sidebar.show') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endcan

                @can('cms.manage')
                    @php($cmsNav = nav_active(['admin.cms.*', 'admin.cms.pages.*']))
                    <li @class(['sidebar-dropdown', 'active' => $cmsNav['is_active'], 'open' => $cmsNav['is_active']])>
                        <a href="#" @class(['active' => $cmsNav['is_active']])>
                            <i class="fas fa-sitemap" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.website_cms') }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $cmsNav['is_active']])>
                            <ul>
                                @php($cmsHomeNav = nav_active('admin.cms.home.*'))
                                <li @class(['active' => $cmsHomeNav['is_active']])>
                                    <a href="{{ route('admin.cms.home.edit', ['lang' => $locale]) }}"
                                       @class(['current-page' => $cmsHomeNav['is_active']])>
                                        {{ __('admin.sidebar.home_page') }}
                                    </a>
                                </li>
                                @php($cmsWhoNav = nav_active('admin.cms.whoweare.*'))
                                <li @class(['active' => $cmsWhoNav['is_active']])>
                                    <a href="{{ route('admin.cms.whoweare.edit', ['lang' => $locale]) }}"
                                       @class(['current-page' => $cmsWhoNav['is_active']])>
                                        {{ __('admin.sidebar.who_we_are') }}
                                    </a>
                                </li>
                                @php($cmsContactNav = nav_active('admin.cms.contact.*'))
                                <li @class(['active' => $cmsContactNav['is_active']])>
                                    <a href="{{ route('admin.cms.contact.edit', ['lang' => $locale]) }}"
                                       @class(['current-page' => $cmsContactNav['is_active']])>
                                        {{ __('admin.sidebar.contact_us') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endcan

                @canany(['contact_submissions.view', 'contact_submissions.delete'])
                    @php($contactSubmissionsNav = nav_active('admin.contact_submissions.*'))
                    <li @class(['sidebar-dropdown', 'active' => $contactSubmissionsNav['is_active'], 'open' => $contactSubmissionsNav['is_active']])>
                        <a href="#" @class(['active' => $contactSubmissionsNav['is_active']])>
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <span class="menu-text">{{ __('admin.sidebar.contact_submissions') }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $contactSubmissionsNav['is_active']])>
                            <ul>
                                <li @class(['active' => $contactSubmissionsNav['is_active']])>
                                    <a href="{{ route('admin.contact_submissions.index', ['lang' => $locale]) }}"
                                       @class(['current-page' => $contactSubmissionsNav['is_active']])>
                                        {{ __('admin.sidebar.all_submissions') }}
                                    </a>
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

        .sidebar-menu ul li.open > a {
            background: #edf3ff;
        }

        .sidebar-menu ul li.open > a i {
            color: #41A8A6;
        }

        .sidebar-menu ul li.open .sidebar-submenu,
        .sidebar-menu .sidebar-submenu.open {
            display: block;
        }

        .sidebar-menu .sidebar-dropdown.open > a:after {
            transform: rotate(-180deg);
        }
    </style>
@endpush
