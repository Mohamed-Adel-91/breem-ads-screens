@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.ads.index', ['lang' => $lang]);
        $showUrl = route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]);

        $adTitle = data_get($ad->getTranslations('title'), app()->getLocale())
            ?: __('admin.ads.untitled', ['id' => $ad->id]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'subtitle' => __('admin.ads.edit_subtitle'),
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.ads_system_all_ads'), 'url' => $indexUrl],
                ['label' => $adTitle, 'url' => $showUrl],
                ['label' => __('admin.forms.edit')],
            ],
            'secondaryAction' => [
                'href' => $showUrl,
                'label' => __('admin.ads.actions.back_to_details'),
                'icon' => 'arrow-left',
            ],
        ])

        <form method="POST"
              action="{{ route('admin.ads.update', ['lang' => $lang, 'ad' => $ad->id]) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.ads.partials.form', ['creativeRequired' => false])

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
