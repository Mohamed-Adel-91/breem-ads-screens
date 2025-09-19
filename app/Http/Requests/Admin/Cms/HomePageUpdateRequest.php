<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;

class HomePageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'banner_video' => ['nullable', 'file', 'mimetypes:video/mp4', 'max:51200'],
            'banner_autoplay' => ['nullable', 'boolean'],
            'banner_loop' => ['nullable', 'boolean'],
            'banner_muted' => ['nullable', 'boolean'],
            'banner_controls' => ['nullable', 'boolean'],
            'banner_playsinline' => ['nullable', 'boolean'],

            'partners' => ['nullable', 'array'],
            'partners.*.image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'partners.*.alt_ar' => ['nullable', 'string', 'max:255'],
            'partners.*.alt_en' => ['nullable', 'string', 'max:255'],
            'partners.*.order' => ['nullable', 'integer', 'min:1'],

            'about_title_ar' => ['required', 'string', 'max:255'],
            'about_title_en' => ['required', 'string', 'max:255'],
            'about_desc_ar' => ['required', 'string'],
            'about_desc_en' => ['required', 'string'],
            'about_readmore_text_ar' => ['nullable', 'string', 'max:255'],
            'about_readmore_text_en' => ['nullable', 'string', 'max:255'],
            'about_readmore_link' => ['nullable', 'string', 'max:255'],

            'stats' => ['nullable', 'array'],
            'stats.*.icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'stats.*.number_ar' => ['nullable', 'string', 'max:50'],
            'stats.*.number_en' => ['nullable', 'string', 'max:50'],
            'stats.*.label_ar' => ['nullable', 'string', 'max:255'],
            'stats.*.label_en' => ['nullable', 'string', 'max:255'],

            'where_title_ar' => ['required', 'string', 'max:255'],
            'where_title_en' => ['required', 'string', 'max:255'],
            'where_brochure_text_ar' => ['nullable', 'string', 'max:255'],
            'where_brochure_text_en' => ['nullable', 'string', 'max:255'],
            'where_brochure_icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'where_brochure_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'where_brochure_link' => ['nullable', 'string', 'max:255'],

            'where_items' => ['nullable', 'array'],
            'where_items.*.image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'where_items.*.overlay_ar' => ['nullable', 'string', 'max:255'],
            'where_items.*.overlay_en' => ['nullable', 'string', 'max:255'],
            'where_items.*.order' => ['nullable', 'integer', 'min:1'],

            'cta_title_ar' => ['required', 'string', 'max:255'],
            'cta_title_en' => ['required', 'string', 'max:255'],
            'cta_text_ar' => ['required', 'string'],
            'cta_text_en' => ['required', 'string'],
            'cta_link_text_ar' => ['required', 'string', 'max:255'],
            'cta_link_text_en' => ['required', 'string', 'max:255'],
            'cta_link_url' => ['required', 'string', 'max:255'],
            'cta_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cta_overlay_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}

