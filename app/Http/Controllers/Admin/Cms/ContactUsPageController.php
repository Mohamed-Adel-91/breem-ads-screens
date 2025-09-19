<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ContactUsPageUpdateRequest;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactUsPageController extends Controller
{
    public function __construct(private FileServiceInterface $fileService)
    {
    }

    public function edit(string $lang = null)
    {
        $page = $this->loadPage();
        $sections = $page->sections->keyBy('type');

        return view('admin.cms.contact_us.edit', compact('page', 'sections'));
    }

    public function update(ContactUsPageUpdateRequest $request, string $lang = null)
    {
        $page = $this->loadPage();

        DB::transaction(function () use ($request, $page) {
            $sections = $page->sections->keyBy('type');

            if ($banner = $sections->get('second_banner')) {
                $this->updateBanner($request, $banner);
            }

            if ($contact = $sections->get('contact_us')) {
                $this->updateContactSection($request, $contact);
            }

            if ($map = $sections->get('map')) {
                $this->updateMapSection($request, $map);
            }

            if ($bottom = $sections->get('bottom_banner')) {
                $this->updateBottomBanner($request, $bottom);
            }

            if ($ads = $sections->get('contact_form_ads')) {
                $this->updateFormSection($request, $ads, 'ads', 'ads', ['labels' => true, 'radio' => true, 'options' => true]);
            }

            if ($screens = $sections->get('contact_form_screens')) {
                $this->updateFormSection($request, $screens, 'screens', 'screens', ['labels' => true, 'radio' => true, 'options' => true]);
            }

            if ($create = $sections->get('contact_form_create')) {
                $this->updateFormSection($request, $create, 'create', 'create', ['labels' => true]);
            }

            if ($faq = $sections->get('contact_form_faq')) {
                $this->updateFormSection($request, $faq, 'faq', 'faq', ['labels' => true]);
            }
        });

        try {
            Cache::forget('page.contact-us');
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', __('admin.cms.saved'));
    }

    private function loadPage(): Page
    {
        return Page::where('slug', 'contact-us')
            ->with(['sections' => function ($query) {
                $query->with(['items' => function ($itemQuery) {
                    $itemQuery->orderBy('order');
                }])->orderBy('order');
            }])
            ->firstOrFail();
    }

    private function updateBanner(ContactUsPageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $imagePath = $this->fileService->uploadCmsFile(
            $request,
            'banner_image',
            'upload/cms/contact/banner',
            data_get($data, 'en.image_path')
        );

        if ($imagePath !== null) {
            foreach (['ar', 'en'] as $locale) {
                $data[$locale]['image_path'] = $imagePath;
            }
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updateContactSection(ContactUsPageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];

        foreach (['ar', 'en'] as $locale) {
            $data[$locale]['title'] = $request->input('contact_title_' . $locale);
            $data[$locale]['subtitle'] = $request->input('contact_subtitle_' . $locale);
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updateMapSection(ContactUsPageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $background = $this->fileService->uploadCmsFile(
            $request,
            'map_background',
            'upload/cms/contact/map',
            data_get($data, 'en.background_image_path')
        );

        foreach (['ar', 'en'] as $locale) {
            if ($background !== null) {
                $data[$locale]['background_image_path'] = $background;
            }

            $data[$locale]['title'] = $request->input('map_title_' . $locale);
            $data[$locale]['address'] = $request->input('map_address_' . $locale);
            $data[$locale]['phone_label'] = $request->input('map_phone_label_' . $locale);
            $data[$locale]['whatsapp_label'] = $request->input('map_whatsapp_label_' . $locale);
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updateBottomBanner(ContactUsPageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $imagePath = $this->fileService->uploadCmsFile(
            $request,
            'bottom_banner_image',
            'upload/cms/contact/bottom',
            data_get($data, 'en.image_path')
        );

        if ($imagePath !== null) {
            foreach (['ar', 'en'] as $locale) {
                $data[$locale]['image_path'] = $imagePath;
            }
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updateFormSection(ContactUsPageUpdateRequest $request, PageSection $section, string $prefix, string $folder, array $structures): void
    {
        $data = $section->section_data ?? [];
        $baseFolder = 'upload/cms/contact/' . $folder;

        $cardImage1 = $this->fileService->uploadCmsFile(
            $request,
            $prefix . '_card_image1',
            $baseFolder,
            data_get($data, 'en.card_image1')
        );

        $cardImage2 = $this->fileService->uploadCmsFile(
            $request,
            $prefix . '_card_image2',
            $baseFolder,
            data_get($data, 'en.card_image2')
        );

        foreach (['ar', 'en'] as $locale) {
            if ($cardImage1 !== null) {
                $data[$locale]['card_image1'] = $cardImage1;
            }
            if ($cardImage2 !== null) {
                $data[$locale]['card_image2'] = $cardImage2;
            }

            $data[$locale]['card_text'] = $request->input($prefix . '_card_text_' . $locale);
            $data[$locale]['modal_title'] = $request->input($prefix . '_modal_title_' . $locale);
            $data[$locale]['submit_text'] = $request->input($prefix . '_submit_text_' . $locale);
        }

        if (!empty($structures['labels'])) {
            $data['ar']['labels'] = $this->decodeAssociative(
                $request->input($prefix . '_labels_ar'),
                $prefix . '_labels_ar',
                data_get($data, 'ar.labels', [])
            );
            $data['en']['labels'] = $this->decodeAssociative(
                $request->input($prefix . '_labels_en'),
                $prefix . '_labels_en',
                data_get($data, 'en.labels', [])
            );
        }

        if (!empty($structures['radio'])) {
            $data['ar']['radio'] = $this->decodeAssociative(
                $request->input($prefix . '_radio_ar'),
                $prefix . '_radio_ar',
                data_get($data, 'ar.radio', [])
            );
            $data['en']['radio'] = $this->decodeAssociative(
                $request->input($prefix . '_radio_en'),
                $prefix . '_radio_en',
                data_get($data, 'en.radio', [])
            );
        }

        if (!empty($structures['options'])) {
            $data['ar']['options'] = $this->decodeAssociative(
                $request->input($prefix . '_options_ar'),
                $prefix . '_options_ar',
                data_get($data, 'ar.options', [])
            );
            $data['en']['options'] = $this->decodeAssociative(
                $request->input($prefix . '_options_en'),
                $prefix . '_options_en',
                data_get($data, 'en.options', [])
            );
        }

        $section->section_data = $data;
        $section->save();
    }

    private function decodeAssociative(?string $value, string $field, array $fallback = []): array
    {
        if ($value === null) {
            return $fallback;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValidationException::withMessages([
                $field => __('admin.cms.invalid_json'),
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => __('admin.cms.invalid_json'),
            ]);
        }

        return $decoded;
    }
}

