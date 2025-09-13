<!-- Header start -->
<header class="header">
    <div class="toggle-btns">
        <a id="toggle-sidebar" href="#">
            <i class="icon-list"></i>
        </a>
        <a id="pin-sidebar" href="#">
            <i class="icon-list"></i>
        </a>
    </div>
    <div class="header-items">
        <!-- Header actions start -->
        <ul class="header-actions">
            <li class="language-switch">
                <a href="{{ route(Route::currentRouteName(), array_merge(request()->route()->parameters(), ['lang' => app()->getLocale() === 'ar' ? 'en' : 'ar'])) }}">
                    {{ app()->getLocale() === 'ar' ? __('admin.header.english') : __('admin.header.arabic') }}
                </a>
            </li>
            <li class="dropdown">
                <a href="#" id="userSettings" class="user-settings" data-toggle="dropdown" aria-haspopup="true">
                    <span class="user-name">
                        {{ Auth::guard('admin')->user()->first_name }}
                        {{ Auth::guard('admin')->user()->last_name }}
                    </span>
                    <span class="avatar">
                        @if (Auth::guard('admin')->user()->profile_picture)
                            <img src="{{ asset( Auth::guard('admin')->user()->image_path) }}"
                                alt="avatar">
                        @else
                            <img src="{{ asset('assets/img/default-avatar.jpg') }}" alt="avatar">
                        @endif
                        <span class="status online"></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userSettings">
                    <div class="header-profile-actions">
                        <div class="header-user-profile">
                            <div class="header-user">
                                @if (Auth::guard('admin')->user()->profile_picture)
                                    <img src="{{ asset( Auth::guard('admin')->user()->image_path) }}"
                                        alt="Admin Template">
                                @else
                                    <img src="{{ asset('assets/img/default-avatar.jpg') }}" alt="Admin Template">
                                @endif
                            </div>
                            <h5>
                                {{ Auth::guard('admin')->user()->first_name }}
                                {{ Auth::guard('admin')->user()->last_name }}
                            </h5>
                            @php
                                $roles = Auth::guard('admin')->user()->getRoleNames();
                            @endphp
                            <p>{{ $roles->implode(', ') }}</p>
                        </div>
                        <a href="{{ route('admin.profile.edit',['lang' => app()->getLocale()]) }}"><i class="icon-settings1"></i> {{ __('admin.header.my_profile') }}</a>
                        @if (Auth::guard('admin')->user()->hasRole('super-admin'))
                            <a href="{{ route('admin.admins.create',['lang' => app()->getLocale()]) }}"><i class="icon-user1"></i> {{ __('admin.header.create_admin') }}</a>
                            <a href="{{ route('admin.admins.index',['lang' => app()->getLocale()]) }}"><i class="icon-users"></i> {{ __('admin.header.admins_list') }}</a>
                            <a href="{{ route('admin.activity_logs.index',['lang' => app()->getLocale()]) }}"
                                class="{{ Request::is('dashboard/activity-logs') ? 'current-page' : '' }}">
                                <i><i class="fas fa-history"></i></i> {{ __('admin.header.activity_logs') }}
                            </a>
                        @endif
                        <a href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="icon-log-out1"></i> {{ __('admin.header.sign_out') }}
                        </a>
                        <form id="logout-form" action="{{ route('admin.logout',['lang' => app()->getLocale()]) }}" method="POST"
                            style="display: none;">
                            @csrf
                            @method('POST')
                        </form>
                    </div>
                </div>
            </li>
        </ul>
        <!-- Header actions end -->
    </div>
</header>
<!-- Header end -->
<!-- Page header start -->
<div class="page-header">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('admin.header.home') }}</li>
        <li class="breadcrumb-item active">{{ $pageName }}</li>
    </ol>
</div>
<!-- Page header end -->
