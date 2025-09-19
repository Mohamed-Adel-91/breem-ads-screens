<?php

namespace App\Http\Controllers\Admin\Cms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContactUsPageContentController extends BasePageContentController
{
    public function edit(string $lang)
    {
        $page = $this->loadPage('contact-us');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('second_banner');
        $contact = $sections->get('contact_us');
        $map = $sections->get('map');
        $bottom = $sections->get('bottom_banner');
        $ads = $sections->get('contact_form_ads');
        $screens = $sections->get('contact_form_screens');
        $create = $sections->get('contact_form_create');
        $faq = $sections->get('contact_form_faq');

        return view('admin.cms.contact-us.edit', [
            'page' => $page,
            'locales' => $this->locales,
            'banner' => $banner,
            'bannerData' => $this->getSectionTranslations($banner),
            'contact' => $contact,
            'contactData' => $this->getSectionTranslations($contact),
            'map' => $map,
            'mapData' => $this->getSectionTranslations($map),
            'bottom' => $bottom,
            'bottomData' => $this->getSectionTranslations($bottom),
            'adsForm' => $ads,
            'adsData' => $this->getSectionTranslations($ads),
            'screensForm' => $screens,
            'screensData' => $this->getSectionTranslations($screens),
            'createForm' => $create,
            'createData' => $this->getSectionTranslations($create),
            'faqForm' => $faq,
            'faqData' => $this->getSectionTranslations($faq),
        ]);
    }

    public function update(string $lang, Request $request)
    {
        $page = $this->loadPage('contact-us');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('second_banner');
        $contact = $sections->get('contact_us');
        $map = $sections->get('map');
        $bottom = $sections->get('bottom_banner');
        $ads = $sections->get('contact_form_ads');
        $screens = $sections->get('contact_form_screens');
        $create = $sections->get('contact_form_create');
        $faq = $sections->get('contact_form_faq');

        $rules = [
            'banner.image' => ['nullable', 'file', 'image', 'max:20480'],
            'map.background_image' => ['nullable', 'file', 'image', 'max:20480'],
            'bottom.image' => ['nullable', 'file', 'image', 'max:20480'],
        ];

        foreach ($this->locales as $locale) {
            $rules["contact.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["contact.$locale.subtitle"] = ['nullable', 'string'];

            $rules["map.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["map.$locale.address"] = ['nullable', 'string'];
            $rules["map.$locale.phone_label"] = ['nullable', 'string', 'max:255'];
            $rules["map.$locale.whatsapp_label"] = ['nullable', 'string', 'max:255'];

            $rules["contact_forms.ads.$locale.card_text"] = ['nullable', 'string'];
            $rules["contact_forms.ads.$locale.modal_title"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.ads.$locale.submit_text"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.ads.$locale.labels"] = ['nullable', 'array'];
            $rules["contact_forms.ads.$locale.radio"] = ['nullable', 'array'];
            $rules["contact_forms.ads.$locale.options"] = ['nullable', 'array'];

            $rules["contact_forms.screens.$locale.card_text"] = ['nullable', 'string'];
            $rules["contact_forms.screens.$locale.modal_title"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.screens.$locale.submit_text"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.screens.$locale.labels"] = ['nullable', 'array'];
            $rules["contact_forms.screens.$locale.radio"] = ['nullable', 'array'];
            $rules["contact_forms.screens.$locale.options"] = ['nullable', 'array'];

            $rules["contact_forms.create.$locale.card_text"] = ['nullable', 'string'];
            $rules["contact_forms.create.$locale.modal_title"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.create.$locale.submit_text"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.create.$locale.labels"] = ['nullable', 'array'];

            $rules["contact_forms.faq.$locale.card_text"] = ['nullable', 'string'];
            $rules["contact_forms.faq.$locale.modal_title"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.faq.$locale.submit_text"] = ['nullable', 'string', 'max:255'];
            $rules["contact_forms.faq.$locale.labels"] = ['nullable', 'array'];
        }

        $rules['contact_forms.ads.card_image1'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.ads.card_image2'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.screens.card_image1'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.screens.card_image2'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.create.card_image1'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.create.card_image2'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.faq.card_image1'] = ['nullable', 'file', 'image', 'max:20480'];
        $rules['contact_forms.faq.card_image2'] = ['nullable', 'file', 'image', 'max:20480'];

        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $banner, $contact, $map, $bottom, $ads, $screens, $create, $faq) {
            if ($banner) {
                $this->updateImageOnlySection($request, $banner, 'banner.image', 'cms/contact/banner');
            }

            if ($contact) {
                $this->updateContactSection($request, $contact);
            }

            if ($map) {
                $this->updateMapSection($request, $map);
            }

            if ($bottom) {
                $this->updateImageOnlySection($request, $bottom, 'bottom.image', 'cms/contact/bottom');
            }

            if ($ads) {
                $this->updateContactFormSection($request, $ads, 'ads');
            }

            if ($screens) {
                $this->updateContactFormSection($request, $screens, 'screens');
            }

            if ($create) {
                $this->updateContactFormSection($request, $create, 'create');
            }

            if ($faq) {
                $this->updateContactFormSection($request, $faq, 'faq');
            }
        });

        Cache::forget('page.contact-us');

        return redirect()
            ->route('admin.cms.contact.edit', ['lang' => $lang])
            ->with('success', 'تم تحديث محتوى صفحة تواصل معنا بنجاح.');
    }

    protected function updateImageOnlySection(Request $request, $section, string $field, string $folder): void
    {
        $data = $this->getSectionTranslations($section);
        $existing = $data[$this->primaryLocale]['image_path'] ?? null;
        $image = $this->upload($request, $field, $folder, $existing);

        foreach ($this->locales as $locale) {
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'image_path' => $image,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function updateContactSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);

        foreach ($this->locales as $locale) {
            $payload = $request->input("contact.$locale", []);
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'title' => $payload['title'] ?? null,
                'subtitle' => $payload['subtitle'] ?? null,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function updateMapSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);
        $existing = $data[$this->primaryLocale]['background_image_path'] ?? null;
        $background = $this->upload($request, 'map.background_image', 'cms/contact/map', $existing);

        foreach ($this->locales as $locale) {
            $payload = $request->input("map.$locale", []);
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'background_image_path' => $background,
                'title' => $payload['title'] ?? null,
                'address' => $payload['address'] ?? null,
                'phone_label' => $payload['phone_label'] ?? null,
                'whatsapp_label' => $payload['whatsapp_label'] ?? null,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function updateContactFormSection(Request $request, $section, string $key): void
    {
        $data = $this->getSectionTranslations($section);
        $basePath = "contact_forms.$key";

        $image1Existing = $data[$this->primaryLocale]['card_image1'] ?? null;
        $image2Existing = $data[$this->primaryLocale]['card_image2'] ?? null;

        $image1 = $this->upload($request, "$basePath.card_image1", "cms/contact/$key", $image1Existing);
        $image2 = $this->upload($request, "$basePath.card_image2", "cms/contact/$key", $image2Existing);

        foreach ($this->locales as $locale) {
            $payload = $request->input("$basePath.$locale", []);

            $options = [];
            foreach (($payload['options'] ?? []) as $optionKey => $value) {
                $lines = preg_split("/(\r\n|\n|\r)/", (string) $value);
                $options[$optionKey] = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
            }

            $data[$locale] = array_merge($data[$locale] ?? [], [
                'card_image1' => $image1,
                'card_image2' => $image2,
                'card_text' => $payload['card_text'] ?? null,
                'modal_title' => $payload['modal_title'] ?? null,
                'submit_text' => $payload['submit_text'] ?? null,
                'labels' => $payload['labels'] ?? [],
            ]);

            if (array_key_exists('radio', $payload)) {
                $data[$locale]['radio'] = $payload['radio'] ?? [];
            }

            if (!empty($options) || isset($data[$locale]['options'])) {
                $data[$locale]['options'] = $options;
            }
        }

        $section->section_data = $data;
        $section->save();
    }
}
