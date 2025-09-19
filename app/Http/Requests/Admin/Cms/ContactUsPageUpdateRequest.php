<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;

class ContactUsPageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'contact_title_ar' => ['required', 'string', 'max:255'],
            'contact_title_en' => ['required', 'string', 'max:255'],
            'contact_subtitle_ar' => ['required', 'string'],
            'contact_subtitle_en' => ['required', 'string'],

            'map_background' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'map_title_ar' => ['required', 'string', 'max:255'],
            'map_title_en' => ['required', 'string', 'max:255'],
            'map_address_ar' => ['required', 'string'],
            'map_address_en' => ['required', 'string'],
            'map_phone_label_ar' => ['required', 'string', 'max:255'],
            'map_phone_label_en' => ['required', 'string', 'max:255'],
            'map_whatsapp_label_ar' => ['required', 'string', 'max:255'],
            'map_whatsapp_label_en' => ['required', 'string', 'max:255'],

            'bottom_banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'ads_card_image1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'ads_card_image2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'ads_card_text_ar' => ['required', 'string'],
            'ads_card_text_en' => ['required', 'string'],
            'ads_modal_title_ar' => ['required', 'string', 'max:255'],
            'ads_modal_title_en' => ['required', 'string', 'max:255'],
            'ads_submit_text_ar' => ['required', 'string', 'max:255'],
            'ads_submit_text_en' => ['required', 'string', 'max:255'],
            'ads_labels_ar' => ['nullable', 'string'],
            'ads_labels_en' => ['nullable', 'string'],
            'ads_radio_ar' => ['nullable', 'string'],
            'ads_radio_en' => ['nullable', 'string'],
            'ads_options_ar' => ['nullable', 'string'],
            'ads_options_en' => ['nullable', 'string'],

            'screens_card_image1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'screens_card_image2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'screens_card_text_ar' => ['required', 'string'],
            'screens_card_text_en' => ['required', 'string'],
            'screens_modal_title_ar' => ['required', 'string', 'max:255'],
            'screens_modal_title_en' => ['required', 'string', 'max:255'],
            'screens_submit_text_ar' => ['required', 'string', 'max:255'],
            'screens_submit_text_en' => ['required', 'string', 'max:255'],
            'screens_labels_ar' => ['nullable', 'string'],
            'screens_labels_en' => ['nullable', 'string'],
            'screens_radio_ar' => ['nullable', 'string'],
            'screens_radio_en' => ['nullable', 'string'],
            'screens_options_ar' => ['nullable', 'string'],
            'screens_options_en' => ['nullable', 'string'],

            'create_card_image1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'create_card_image2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'create_card_text_ar' => ['required', 'string'],
            'create_card_text_en' => ['required', 'string'],
            'create_modal_title_ar' => ['required', 'string', 'max:255'],
            'create_modal_title_en' => ['required', 'string', 'max:255'],
            'create_submit_text_ar' => ['required', 'string', 'max:255'],
            'create_submit_text_en' => ['required', 'string', 'max:255'],
            'create_labels_ar' => ['nullable', 'string'],
            'create_labels_en' => ['nullable', 'string'],

            'faq_card_image1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'faq_card_image2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'faq_card_text_ar' => ['required', 'string'],
            'faq_card_text_en' => ['required', 'string'],
            'faq_modal_title_ar' => ['required', 'string', 'max:255'],
            'faq_modal_title_en' => ['required', 'string', 'max:255'],
            'faq_submit_text_ar' => ['required', 'string', 'max:255'],
            'faq_submit_text_en' => ['required', 'string', 'max:255'],
            'faq_labels_ar' => ['nullable', 'string'],
            'faq_labels_en' => ['nullable', 'string'],
        ];
    }
}

