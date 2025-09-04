<?php

namespace App\Http\Requests;

use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'writer_id'       => ['required', Rule::exists('writers', 'id')->whereNull('deleted_at')],
            'title'           => ['required', 'string', 'max:255'],
            'short_desc'      => ['required', 'string', 'max:1000'],
            'details'         => ['required', 'string'],
            'added_date'      => ['required', 'date'],
            'scheduale_date'  => ['nullable', 'date', 'after_or_equal:added_date'],
            'related_tags'    => ['nullable', 'string'],
            'home_img'        => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'thumb_img'       => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'number_of_reads' => ['nullable', 'integer', 'min:0'],
            'home'            => ['sometimes', 'boolean'],
            'hidden'          => ['sometimes', 'boolean'],
            'altText'         => ['nullable', 'string', 'max:255'],
            'is_old'          => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'writer_id'       => 'الكاتب',
            'title'           => 'العنوان',
            'short_desc'      => 'الوصف المختصر',
            'details'         => 'التفاصيل',
            'added_date'      => 'تاريخ الإضافة',
            'scheduale_date'  => 'تاريخ الجدولة',
            'related_tags'    => 'الوسوم المرتبطة',
            'home_img'        => 'صورة الرئيسية',
            'thumb_img'       => 'الصورة المصغرة',
            'number_of_reads' => 'عدد القراءات',
            'home'            => 'إظهار في الرئيسية',
            'hidden'          => 'مخفي',
            'altText'         => 'النص البديل',
            'is_old'          => 'خبر قديم',
        ];
    }

    public function messages(): array
    {
        return [
            'writer_id.required' => 'يجب اختيار كاتب.',
            'writer_id.exists'   => 'الكاتب غير موجود أو محذوف.',
            'title.required'     => 'العنوان مطلوب.',
            'title.max'          => 'العنوان لا يزيد عن 255 حرفًا.',
            'short_desc.required' => 'الوصف المختصر مطلوب.',
            'short_desc.max'     => 'الوصف المختصر طويل جدًا.',
            'details.required'   => 'التفاصيل مطلوبة.',
            'added_date.required' => 'تاريخ الإضافة مطلوب.',
            'scheduale_date.after_or_equal' => 'تاريخ الجدولة يجب أن يكون بعد أو مساوٍ لتاريخ الإضافة.',
            'home_img.image'     => 'ملف الصورة غير صالح.',
            'home_img.mimes'     => 'الصورة يجب أن تكون من الأنواع: jpg, jpeg, png, gif, webp.',
            'thumb_img.image'    => 'ملف الصورة غير صالح.',
            'thumb_img.mimes'    => 'الصورة يجب أن تكون من الأنواع: jpg, jpeg, png, gif, webp.',
            'number_of_reads.integer' => 'عدد القراءات يجب أن يكون رقمًا صحيحًا.',
            'number_of_reads.min'     => 'عدد القراءات لا يمكن أن يكون سالبًا.',
            'is_old.boolean'          => 'يجب أن يكون خبر قديم أو لا.',
        ];
    }
}
