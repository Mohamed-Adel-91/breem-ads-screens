@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $admin = Auth::guard('admin')->user();
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
        $roles = $admin?->getRoleNames() ?? collect();
        $dashboardMenuItems = app(\App\Services\Admin\MenuBuilder::class)->build('static-sidebar');
        $dashboardIconMap = [
            'dashboard' => 'home',
            'ads_system' => 'monitor',
            'admins_management' => 'shield',
            'users_management' => 'users',
            'website_cms' => 'layout',
            'contact_submissions' => 'inbox',
        ];
    @endphp

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="row align-items-center mb-4">
                    <div class="col">
                        <span class="page-kicker">Breem</span>
                        <h1 class="page-title mb-1">{{ $pageName }}</h1>
                        <p class="text-muted mb-0">{{ __('admin.dashboard.logged_in_message') }}</p>
                    </div>
                </div>

                <div class="card dashboard-welcome mb-4">
                    <div class="card-body">
                        <span class="welcome-icon" aria-hidden="true">
                            <i class="fe fe-grid"></i>
                        </span>
                        <h2 class="h4 mb-2">
                            {{ __('admin.messages.welcome') }}
                            @if ($adminName !== '')
                                {{ $adminName }}
                            @endif
                        </h2>
                        <p class="mb-3">{{ __('admin.header.panel_title') }}</p>

                        @foreach ($roles as $role)
                            <span class="dashboard-role">{{ $role }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    @foreach ($dashboardMenuItems as $item)
                        @php
                            $targetUrl = $item['url'] ?? '#';
                            if ($targetUrl === '#' && !empty($item['children'])) {
                                $targetUrl = $item['children'][0]['url'] ?? '#';
                            }

                            $itemTitle = \App\Services\Admin\MenuBuilder::title($item);
                            $itemIcon = $dashboardIconMap[$item['key'] ?? ''] ?? 'grid';
                        @endphp

                        @if ($targetUrl !== '#')
                            <div class="col-sm-6 col-xl-4 mb-3">
                                <a href="{{ $targetUrl }}"
                                   class="card quick-link-card text-decoration-none"
                                   aria-label="{{ $itemTitle }}">
                                    <div class="card-body d-flex align-items-center">
                                        <span class="quick-link-icon" aria-hidden="true">
                                            <i class="fe fe-{{ $itemIcon }}"></i>
                                        </span>
                                        <h3 class="mb-0 ml-3 mr-3 flex-grow-1">{{ $itemTitle }}</h3>
                                        <span class="quick-link-arrow" aria-hidden="true">
                                            <i class="fe fe-arrow-right"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
