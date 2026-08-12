@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.ads.index', ['lang' => $lang]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.ads.create_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads'), 'url' => $indexUrl],
                ['label' => __('admin.forms.create')],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.ads.actions.back_to_list'),
                'icon' => 'arrow-left',
            ],
        ])

        <form method="POST"
              action="{{ route('admin.ads.store', ['lang' => $lang]) }}"
              enctype="multipart/form-data">
            @csrf

            @include('admin.ads.partials.form', ['creativeRequired' => true])

            <div class="card">
                <div class="card-body">
                    <x-admin.group-btn class="justify-content-end">
                        <x-admin.btn :href="$indexUrl" variant="light" icon="x">
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
