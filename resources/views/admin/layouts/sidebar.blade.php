<!-- Sidebar wrapper start -->
<nav id="sidebar" class="sidebar-wrapper">
    <!-- Sidebar brand start  -->
    <div class="sidebar-brand d-flex justify-content-center" style="height: 100px;">
        <a href="{{ route('admin.index') }}" style="background-color: #fff;">
            <h4 class="py-4" style="color:#fff;"><img src="{{ asset('logo2.png') }}" style="width: 100%; padding:20px;">
            </h4>
        </a>
    </div>
    <!-- Sidebar brand end  -->
    <!-- Sidebar content start -->
    <div class="sidebar-content">
        <!-- sidebar menu start -->
        <div class="sidebar-menu">
            <ul>
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/newsletter') || Request::is('dashboard/settings/edit') || Request::is('dashboard/seo_metas') || Request::is('dashboard/static_sentences') ? 'active' : '' }}">
                    <a href="#">
                        <i class="icon-settings1"></i>
                        <span class="menu-text">{{ __('admin.sidebar.general_settings') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/settings/edit') ? 'active' : '' }}">
                                <a href="{{ route('admin.settings.edit') }}"
                                    class="{{ Request::is('dashboard/settings/edit') ? 'current-page' : '' }}">{{ __('admin.sidebar.main_settings') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/newsletter') ? 'active' : '' }}">
                                <a href="{{ route('admin.newsletter.index') }}"
                                    class="{{ Request::is('dashboard/newsletter') ? 'current-page' : '' }}">{{ __('admin.sidebar.newsletter') }}</a>
                            </li>
                            {{-- <li class="{{ Request::is('dashboard/seo_metas') ? 'active' : '' }}">
                                <a href="{{ route('admin.seo_metas.index') }}"
                                    class="{{ Request::is('dashboard/seo_metas') ? 'current-page' : '' }}">{{ __('admin.sidebar.seo_metas') }}</a>
                            </li> --}}
                        </ul>
                    </div>
                </li>
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/shapes') ||
                    Request::is('dashboard/brands') ||
                    Request::is('dashboard/feature_categories') ||
                    Request::is('dashboard/features') ||
                    Request::is('dashboard/colors') ||
                    Request::is('dashboard/service_centers') ||
                    Request::is('dashboard/know_more') ||
                    Request::is('dashboard/car_exteriors') ||
                    Request::is('dashboard/car_interiors') ||
                    Request::is('dashboard/car_info_features') ||
                    Request::is('dashboard/car_terms') ||
                    Request::is('dashboard/car_terms_features') ||
                    Request::is('dashboard/car_terms_colors') ||
                    Request::is('dashboard/car_models_colors') ||
                    Request::is('dashboard/car_models')
                        ? 'active'
                        : '' }}">
                    <a href="#">
                        <i><i class="fas fa-car" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.cars_module') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/shapes') ? 'active' : '' }}">
                                <a href="{{ route('admin.shapes.index') }}"
                                    class="{{ Request::is('dashboard/shapes') ? 'current-page' : '' }}">{{ __('admin.sidebar.shapes') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/brands') ? 'active' : '' }}">
                                <a href="{{ route('admin.brands.index') }}"
                                    class="{{ Request::is('dashboard/brands') ? 'current-page' : '' }}">{{ __('admin.sidebar.brands') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/service_centers') ? 'active' : '' }}">
                                <a href="{{ route('admin.service_centers.index') }}"
                                    class="{{ Request::is('dashboard/service_centers') ? 'current-page' : '' }}">{{ __('admin.sidebar.service_centers') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/feature_categories') ? 'active' : '' }}">
                                <a href="{{ route('admin.feature_categories.index') }}"
                                    class="{{ Request::is('dashboard/feature_categories') ? 'current-page' : '' }}">{{ __('admin.sidebar.feature_categories') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/features') ? 'active' : '' }}">
                                <a href="{{ route('admin.features.index') }}"
                                    class="{{ Request::is('dashboard/features') ? 'current-page' : '' }}">{{ __('admin.sidebar.features') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/colors') ? 'active' : '' }}">
                                <a href="{{ route('admin.colors.index') }}"
                                    class="{{ Request::is('dashboard/colors') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_colors') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/car_models') ? 'active' : '' }}">
                                <a href="{{ route('admin.car_models.index') }}"
                                    class="{{ Request::is('dashboard/car_models') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_models') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/know_more') ? 'active' : '' }}">
                                <a href="{{ route('admin.know_more.index') }}"
                                    class="{{ Request::is('dashboard/know_more') ? 'current-page' : '' }}">
                                    {{ __('admin.sidebar.know_more') }}
                                </a>
                            </li>
                            <li class="{{ Request::is('dashboard/car_exteriors') ? 'active' : '' }}">
                                <a href="{{ route('admin.car_exteriors.index') }}"
                                    class="{{ Request::is('dashboard/car_exteriors') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_exteriors') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/car_interiors') ? 'active' : '' }}">
                                <a href="{{ route('admin.car_interiors.index') }}"
                                    class="{{ Request::is('dashboard/car_interiors') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_interiors') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/car_info_features') ? 'active' : '' }}">
                                <a href="{{ route('admin.car_info_features.index') }}"
                                    class="{{ Request::is('dashboard/car_info_features') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_info_features') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/car_terms') ? 'active' : '' }}">
                                <a href="{{ route('admin.car_terms.index') }}"
                                    class="{{ Request::is('dashboard/car_terms') ? 'current-page' : '' }}">{{ __('admin.sidebar.car_terms') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/news') || Request::is('dashboard/writers') || Request::is('dashboard/videos') ? 'active' : '' }}">
                    <a href="#">
                        <i><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-mic-fill" viewBox="0 0 16 16">
                                <path d="M5 3a3 3 0 0 1 6 0v5a3 3 0 0 1-6 0z" />
                                <path
                                    d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5" />
                            </svg></i>
                        <span class="menu-text">{{ __('admin.sidebar.news_videos') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/writers') ? 'active' : '' }}">
                                <a href="{{ route('admin.writers.index') }}"
                                    class="{{ Request::is('dashboard/writers') ? 'current-page' : '' }}">{{ __('admin.sidebar.writers') }}</a>
                            </li>
                        </ul>
                        <ul>
                            <li class="{{ Request::is('dashboard/news') ? 'active' : '' }}">
                                <a href="{{ route('admin.news.index') }}"
                                    class="{{ Request::is('dashboard/news') ? 'current-page' : '' }}">{{ __('admin.sidebar.news') }}</a>
                            </li>
                        </ul>
                        <ul>
                            <li class="{{ Request::is('dashboard/videos') ? 'active' : '' }}">
                                <a href="{{ route('admin.videos.index') }}"
                                    class="{{ Request::is('dashboard/videos') ? 'current-page' : '' }}">{{ __('admin.sidebar.videos') }}</a>
                            </li>
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
                                <a href="{{ route('admin.users.index') }}"
                                    class="{{ Request::is('dashboard/users') ? 'current-page' : '' }}">{{ __('admin.sidebar.show') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/insurances') || Request::is('dashboard/insurance_orders') ? 'active' : '' }}">
                    <a href="#">
                        <i><i class="fas fa-shield-alt" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.insurance') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/insurances') ? 'active' : '' }}">
                                <a href="{{ route('admin.insurances.index') }}"
                                    class="{{ Request::is('dashboard/insurances') ? 'current-page' : '' }}">{{ __('admin.sidebar.insurance_programs') }}
                                </a>
                            </li>
                            <li class="{{ Request::is('dashboard/insurance_orders') ? 'active' : '' }}">
                                <a href="{{ route('admin.insurance_orders.index') }}"
                                    class="{{ Request::is('dashboard/insurance_orders') ? 'current-page' : '' }}">{{ __('admin.sidebar.insurance_orders') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li
                    class="sidebar-dropdown {{ Request::is('dashboard/banks') || Request::is('dashboard/installment_programs') ? 'active' : '' }}">
                    <a href="#">
                        <i><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.installment') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/banks') ? 'active' : '' }}">
                                <a href="{{ route('admin.banks.index') }}"
                                    class="{{ Request::is('dashboard/banks') ? 'current-page' : '' }}">{{ __('admin.sidebar.banks') }}</a>
                            </li>
                            <li class="{{ Request::is('dashboard/installment_programs') ? 'active' : '' }}">
                                <a href="{{ route('admin.installment_programs.index') }}"
                                    class="{{ Request::is('dashboard/installment_programs') ? 'current-page' : '' }}">{{ __('admin.sidebar.installment_programs') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="sidebar-dropdown {{ Request::is('dashboard/orders') ? 'active' : '' }}">
                    <a href="#">
                        <i><i class="fas fa-credit-card" aria-hidden="true"></i></i>
                        <span class="menu-text">{{ __('admin.sidebar.finance_and_payment') }}</span>
                    </a>
                    <div class="sidebar-submenu">
                        <ul>
                            <li class="{{ Request::is('dashboard/orders') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.index') }}"
                                    class="{{ Request::is('dashboard/orders') ? 'current-page' : '' }}"><small
                                        class="text-muted">
                                        ({{ __('admin.sidebar.cash_and_installments') }})</small>{{ __('admin.sidebar.orders') }}
                                </a>
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
