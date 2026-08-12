@props([
    'items' => [],
    'variant' => 'static-sidebar',
    'level' => 0,
])

{{--
    Renders the canonical static admin sidebar menu (Bootstrap 4 collapse).

    The Tailwind/Alpine `topnav`, `topnav-dropdown`, `topnav-responsive` and
    legacy `sidebar` variants were removed in Phase 8 together with their only
    consumers (admin/layouts/navigation.blade.php and the legacy sidebar). The
    `variant` prop is kept so the existing call site stays unchanged.
--}}
@php
    $iconMap = [
        'dashboard' => 'home',
        'ads_system' => 'monitor',
        'ads_system_all_ads' => 'film',
        'ads_system_schedules' => 'calendar',
        'ads_system_screens' => 'tv',
        'ads_system_places' => 'map-pin',
        'ads_system_monitoring' => 'activity',
        'ads_system_reports' => 'bar-chart-2',
        'ads_system_logs' => 'file-text',
        'admins_management' => 'shield',
        'admins' => 'user-check',
        'permissions' => 'key',
        'roles' => 'users',
        'users_management' => 'users',
        'website_cms' => 'layout',
        'contact_submissions' => 'inbox',
        'contact_submissions_all' => 'mail',
    ];
@endphp

@if (!empty($items))
    <ul @class([
        'navbar-nav flex-fill w-100 mb-2 breem-menu' => $level === 0,
        'navbar-nav flex-fill w-100 mb-0 breem-menu breem-submenu' => $level > 0,
    ])>
        @foreach ($items as $item)
            @php
                $hasChildren = !empty($item['children']);
                $title = \App\Services\Admin\MenuBuilder::title($item);
                $url = $item['url'] ?? '#';
                $isActive = (bool) ($item['is_active'] ?? false);
                $isOpen = (bool) ($item['is_open'] ?? false);
                $itemKey = (string) ($item['key'] ?? '');
                $icon = $iconMap[$itemKey] ?? ($level === 0 ? 'grid' : 'circle');
                $collapseId = 'admin-menu-' . substr(md5($itemKey . '-' . $level), 0, 10);
            @endphp

            <li @class(['nav-item', 'dropdown' => $hasChildren, 'active' => $isActive])>
                @if ($hasChildren)
                    <a href="#{{ $collapseId }}"
                       data-toggle="collapse"
                       aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                       aria-controls="{{ $collapseId }}"
                       @class(['dropdown-toggle nav-link', 'active' => $isActive, 'collapsed' => !$isOpen])>
                        <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
                        <span class="item-text">{{ $title }}</span>
                    </a>
                    <div id="{{ $collapseId }}" @class(['collapse', 'show' => $isOpen])>
                        <x-admin.menu :items="$item['children']" :variant="$variant" :level="$level + 1" />
                    </div>
                @else
                    <a href="{{ $url }}" @class(['nav-link', 'active' => $isActive])>
                        <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
                        <span class="item-text">{{ $title }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
@endif
