<!-- Sidebar wrapper start -->
<nav id="sidebar" class="sidebar-wrapper">
    <!-- Sidebar brand start  -->
    <div class="sidebar-brand d-flex justify-content-center"
        style="height: 100px; margin-bottom: 10px;">
        <a href="{{ route('admin.index', ['lang' => app()->getLocale()]) }}">
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
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/settings/edit') || Request::is('dashboard/seo_metas') ? 'active' : '' }}">
                    <a href="#">
                        <i class="icon-settings1"></i>
                        <span class="menu-text">{{ __('admin.sidebar.general_settings') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/settings/edit') ? 'active' : '' }}">
                                <a href="{{ route('admin.settings.edit',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/settings/edit') ? 'current-page' : '' }}">{{ __('admin.sidebar.main_settings') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/seo_metas') ? 'active' : '' }}">
                                <a href="{{ route('admin.seo_metas.index',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/seo_metas') ? 'current-page' : '' }}">{{ __('admin.sidebar.seo_metas') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                {{-- <li
                    class="sidebar-dropdown {{ Request::is('dashboard/users') || Request::is('dashboard/users/show') ? 'active' : '' }}">
                    <a href="#">
                        <i><i class="fas fa-user-cog" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.users_management') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/users') ? 'active' : '' }}">
                                <a href="{{ route('admin.users.index',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/users') ? 'current-page' : '' }}">{{ __('admin.sidebar.show') }}</a>
                            </li>
                        </ul>
                    </div>
                </li> --}}
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/admins') || Request::is('dashboard/admins/*') || Request::is('dashboard/permissions') || Request::is('dashboard/permissions/*') ? 'active' : '' }}">
                    <a href="#">
                        <i><i class="fas fa-user-shield" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.admins_management') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/admins') || Request::is('dashboard/admins/*') ? 'active' : '' }}">
                                <a href="{{ route('admin.admins.index',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/admins') || Request::is('dashboard/admins/*') ? 'current-page' : '' }}">{{ __('admin.sidebar.admins') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/permissions') || Request::is('dashboard/permissions/*') ? 'active' : '' }}">
                                <a href="{{ route('admin.permissions.index',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/permissions') || Request::is('dashboard/permissions/*') ? 'current-page' : '' }}">{{ __('admin.sidebar.permissions') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/roles') || Request::is('dashboard/roles/*') ? 'active' : '' }}">
                                <a href="{{ route('admin.roles.index',['lang' => app()->getLocale()]) }}"
                                    class="{{ Request::is('dashboard/roles') || Request::is('dashboard/roles/*') ? 'current-page' : '' }}">{{ __('admin.sidebar.roles') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
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
