@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.screens.index', ['lang' => $lang]);
        $showUrl = route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.screens.edit_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.screens'), 'url' => $indexUrl],
                ['label' => $screen->code, 'url' => $showUrl],
                ['label' => __('admin.forms.edit')],
            ],
            'secondaryAction' => [
                'href' => $showUrl,
                'label' => __('admin.screens.actions.back_to_details'),
                'icon' => 'arrow-left',
            ],
        ])

        <form method="POST" action="{{ route('admin.screens.update', ['lang' => $lang, 'screen' => $screen->id]) }}">
            @csrf
            @method('PUT')

            @include('admin.screens.partials.form')

            <div class="card">
                <div class="card-body">
                    <x-admin.group-btn class="justify-content-end">
                        <x-admin.btn :href="$showUrl" variant="light" icon="x">
                            {{ __('admin.buttons.close') }}
                        </x-admin.btn>
                        <x-admin.btn type="submit" icon="save">
                            {{ __('admin.forms.save_button') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>
            </div>
        </form>
    </div>
@endsection
