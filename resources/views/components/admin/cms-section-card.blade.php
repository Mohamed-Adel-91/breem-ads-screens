@props([
    'title',
    'sectionType' => null,
    'sectionId' => null,
    'isActive' => null,
    'bodyClass' => 'card-body',
])

<div {{ $attributes->class(['card mb-4']) }}>
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <div class="mr-auto">
            <h2 class="card-title mb-0 d-inline-block">{{ $title }}</h2>

            @if ($sectionId)
                <x-admin.badge variant="light" class="ml-2">#{{ $sectionId }}</x-admin.badge>
            @endif

            @if ($sectionType)
                <x-admin.badge variant="light" class="ml-1"><code>{{ $sectionType }}</code></x-admin.badge>
            @endif

            @if (!is_null($isActive))
                <x-admin.badge :variant="$isActive ? 'success' : 'danger'" class="ml-1">
                    {{ $isActive ? __('admin.forms.active') : __('admin.cms.inactive') }}
                </x-admin.badge>
            @endif
        </div>

        @isset($actions)
            <div class="admin-actions mt-2 mt-sm-0">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
