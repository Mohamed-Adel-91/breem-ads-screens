@php
    $admin = Auth::guard('admin')->user();
    $targetLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $currentRoute = Route::currentRouteName();
    $localeUrl = $currentRoute
        ? route($currentRoute, array_merge(request()->route()->parameters(), ['lang' => $targetLocale]))
        : url('/' . $targetLocale . '/admin-panel');
    $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
    $avatarUrl = $admin?->profile_picture
        ? $admin->image_path
        : asset('admin-assets/assets/images/default-avatar.jpg');
@endphp

<nav class="topnav navbar navbar-light">
    <button type="button"
            class="navbar-toggler text-muted p-0 collapseSidebar"
            aria-label="{{ __('admin.pages.dashboard.title') }}"
            aria-controls="leftSidebar"
            aria-expanded="false">
        <i class="fe fe-menu fe-20" aria-hidden="true"></i>
    </button>

    <ul class="nav align-items-center">
        <li class="nav-item">
            <a class="nav-link header-action"
               href="{{ $localeUrl }}"
               lang="{{ $targetLocale }}"
               hreflang="{{ $targetLocale }}">
                <i class="fe fe-globe fe-16 mr-2" aria-hidden="true"></i>
                <span>
                    {{ app()->getLocale() === 'ar'
                        ? \App\Support\Lang::t('admin.header.english')
                        : \App\Support\Lang::t('admin.header.arabic') }}
                </span>
            </a>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle header-action pr-0"
               href="#"
               id="breemAdminMenu"
               role="button"
               data-toggle="dropdown"
               aria-haspopup="true"
               aria-expanded="false">
                <span class="header-user-name mr-2">{{ $adminName }}</span>
                <span class="avatar avatar-sm">
                    <img src="{{ $avatarUrl }}"
                         class="avatar-img rounded-circle"
                         alt="{{ $adminName }}">
                </span>
            </a>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="breemAdminMenu">
                <div class="px-3 py-2 border-bottom">
                    <strong class="d-block text-dark">{{ $adminName }}</strong>
                    <small class="text-muted">{{ $admin?->getRoleNames()->implode(', ') }}</small>
                </div>

                <a class="dropdown-item"
                   href="{{ route('admin.profile.edit', ['lang' => app()->getLocale()]) }}">
                    <i class="fe fe-user" aria-hidden="true"></i>
                    <span>{{ __('admin.header.my_profile') }}</span>
                </a>

                <a class="dropdown-item"
                   href="#"
                   onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    <i class="fe fe-log-out" aria-hidden="true"></i>
                    <span>{{ __('admin.header.sign_out') }}</span>
                </a>

                <form id="admin-logout-form"
                      action="{{ route('admin.logout', ['lang' => app()->getLocale()]) }}"
                      method="POST"
                      class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>
