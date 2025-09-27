@props([
    'items' => [],
    'variant' => 'sidebar',
    'level' => 0,
])

@php
    $variant = $variant ?? 'sidebar';
@endphp

@if(!empty($items))
    <ul @class([
        'flex items-center gap-6' => $variant === 'topnav',
        'flex flex-col gap-1 py-1' => $variant === 'topnav-dropdown',
        'space-y-1' => $variant === 'topnav-responsive',
        'list-unstyled ms-3' => $variant === 'sidebar' && $level > 0,
    ])>
        @foreach($items as $item)
            @php
                $hasChildren = !empty($item['children']);
                $title = \App\Services\Admin\MenuBuilder::title($item);
                $icon = $item['icon'] ?? null;
                $url = $item['url'] ?? '#';
                $isActive = (bool)($item['is_active'] ?? false);
                $isOpen = (bool)($item['is_open'] ?? false);
            @endphp

            @if($variant === 'sidebar')
                <li @class([
                    'sidebar-dropdown' => $hasChildren,
                    'active' => $isActive,
                    'open' => $isOpen,
                ])>
                    @if($hasChildren)
                        <a href="#" @class(['active' => $isActive])>
                            @if($icon)
                                <i class="{{ $icon }}"></i>
                            @endif
                            <span class="menu-text">{{ $title }}</span>
                        </a>
                        <div @class(['sidebar-submenu', 'open' => $isOpen])>
                            <x-admin.menu :items="$item['children']" :variant="$variant" :level="$level + 1" />
                        </div>
                    @else
                        <a href="{{ $url }}" @class(['current-page' => $isActive])>
                            @if($icon)
                                <i class="{{ $icon }}"></i>
                            @endif
                            <span class="menu-text">{{ $title }}</span>
                        </a>
                    @endif
                </li>
            @elseif($variant === 'topnav')
                <li class="relative">
                    @if($hasChildren)
                        <div x-data="{ open: false }" class="relative">
                            <button type="button"
                                    @click="open = !open"
                                    @click.outside="open = false"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium leading-5 border-b-2 transition {{ $isActive ? 'border-indigo-500 text-gray-900 focus:outline-none' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300' }}">
                                @if($icon)
                                    <i class="{{ $icon }}"></i>
                                @endif
                                <span>{{ $title }}</span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.939l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-cloak
                                 x-show="open"
                                 x-transition
                                 class="absolute right-0 z-20 mt-2 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                                <x-admin.menu :items="$item['children']" variant="topnav-dropdown" :level="$level + 1" />
                            </div>
                        </div>
                    @else
                        <a href="{{ $url }}"
                           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ $isActive ? 'border-indigo-500 text-gray-900 focus:outline-none' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300' }}">
                            @if($icon)
                                <i class="{{ $icon }}"></i>
                            @endif
                            <span>{{ $title }}</span>
                        </a>
                    @endif
                </li>
            @elseif($variant === 'topnav-responsive')
                <li @class(['border-t border-gray-200' => $level === 0 && !$loop->first])>
                    @if($hasChildren)
                        <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }" class="w-full">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full flex items-center justify-between px-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                                <span class="flex items-center gap-2">
                                    @if($icon)
                                        <i class="{{ $icon }}"></i>
                                    @endif
                                    {{ $title }}
                                </span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.939l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-cloak x-show="open" x-transition class="ps-4">
                                <x-admin.menu :items="$item['children']" variant="topnav-responsive" :level="$level + 1" />
                            </div>
                        </div>
                    @else
                        <a href="{{ $url }}"
                           class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium transition {{ $isActive ? 'border-indigo-500 text-indigo-700 bg-indigo-50 focus:outline-none' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300' }}">
                            @if($icon)
                                <i class="{{ $icon }}"></i>
                            @endif
                            <span>{{ $title }}</span>
                        </a>
                    @endif
                </li>
            @else
                <li>
                    <a href="{{ $url }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isActive ? 'bg-gray-100 font-semibold' : '' }}">
                        @if($icon)
                            <i class="{{ $icon }}"></i>
                        @endif
                        <span>{{ $title }}</span>
                    </a>
                    @if($hasChildren)
                        <x-admin.menu :items="$item['children']" variant="topnav-dropdown" :level="$level + 1" />
                    @endif
                </li>
            @endif
        @endforeach
    </ul>
@endif
